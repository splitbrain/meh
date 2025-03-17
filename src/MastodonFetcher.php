<?php

namespace splitbrain\meh;

use splitbrain\phpcli\CLI;

/**
 * Class to handle fetching and processing posts from Mastodon
 */
class MastodonFetcher
{
    /**
     * @var CLI CLI instance for output
     */
    protected $cli;

    /**
     * @var App Application container
     */
    protected $app;

    /**
     * Constructor
     *
     * @param CLI $cli CLI instance for output
     * @param App $app Application container
     */
    public function __construct(CLI $cli, App $app)
    {
        $this->cli = $cli;
        $this->app = $app;
    }

    /**
     * Fetch posts from a Mastodon account and look for links to the site
     *
     * @param string $account The Mastodon account (e.g., @user@instance.social)
     * @param string|null $instance The Mastodon instance URL
     * @param int $limit Maximum number of posts to fetch
     * @return void
     */
    public function fetchPosts(string $account, ?string $instance = null, int $limit = 100): void
    {
        // Parse the account string to extract username and instance
        if (preg_match('/^@?([^@]+)@(.+)$/', $account, $matches)) {
            $username = $matches[1];
            $instanceHost = $matches[2];
        } else {
            // If no instance in account string, require the instance parameter
            if (empty($instance)) {
                $this->cli->error("Invalid account format. Use @username@instance.social or provide --instance parameter");
                return;
            }
            $username = ltrim($account, '@');
            $instanceHost = parse_url($instance, PHP_URL_HOST) ?: $instance;
        }

        // Normalize instance URL
        if (empty($instance)) {
            $instance = "https://$instanceHost";
        }

        // Remove trailing slash if present
        $instance = rtrim($instance, '/');

        $this->cli->info("Fetching posts from $username@$instanceHost");

        // First, we need to look up the account ID
        $accountId = $this->lookupAccount($instance, $username);
        if (!$accountId) {
            $this->cli->error("Could not find Mastodon account: $username@$instanceHost");
            return;
        }

        $this->cli->info("Found account ID: $accountId");

        // Get the site URL from config
        $siteUrl = $this->app->conf('site_url');
        $this->cli->info("Looking for posts linking to: $siteUrl");

        // Fetch all statuses with pagination
        $allStatuses = $this->fetchAllStatuses($instance, $accountId, $limit);
        if (empty($allStatuses)) {
            $this->cli->warning("No posts found for this account");
            return;
        }

        $this->cli->info("Fetched " . count($allStatuses) . " posts");

        // Process the statuses to find links to our site
        $matchingPosts = [];
        $db = $this->app->db();
        $importCount = 0;

        foreach ($allStatuses as $status) {
            // Skip non-public posts
            if ($status->visibility !== 'public') {
                continue;
            }

            // Check if the post contains a link to our site
            $foundLink = false;
            $postPath = null;

            // Check content for links
            if (isset($status->content)) {
                // Extract URLs from the HTML content
                if (preg_match_all('/<a[^>]+href="([^"]+)"[^>]*>/i', $status->content, $matches)) {
                    foreach ($matches[1] as $url) {
                        if (strpos($url, $siteUrl) === 0) {
                            $foundLink = true;
                            $postPath = parse_url($url, PHP_URL_PATH);
                            break;
                        }
                    }
                }
            }

            // Also check card links if available
            if (!$foundLink && isset($status->card) && isset($status->card->url)) {
                $cardUrl = $status->card->url;
                if (strpos($cardUrl, $siteUrl) === 0) {
                    $foundLink = true;
                    $postPath = parse_url($cardUrl, PHP_URL_PATH);
                }
            }

            if ($foundLink) {
                $matchingPosts[] = [
                    'id' => $status->id,
                    'url' => $status->url,
                    'created_at' => $status->created_at,
                    'content' => strip_tags($status->content),
                    'post_path' => $postPath,
                    'author' => $username . '@' . $instanceHost
                ];

                // Extract the text content
                $text = strip_tags($status->content);

                // Insert into database

                try {
                    $db->query(
                        'INSERT INTO comments 
                        (post, author, website, text, html, status, created_at) 
                        VALUES 
                        (?, ?, ?, ?, ?, ?, ?)',
                        [
                            $postPath,
                            $username . '@' . $instanceHost,
                            $status->url,
                            $text,
                            $status->content,
                            'approved',
                            date('Y-m-d H:i:s', strtotime($status->created_at))
                        ]
                    );
                    $importCount++;
                    $this->cli->success("Imported comment from Mastodon post: " . $status->url);
                } catch (\Exception $e) {
                    $this->cli->error("Error importing comment: " . $e->getMessage());
                }
            }
        }

        $this->cli->success("Found " . count($matchingPosts) . " posts linking to your site");
        $this->cli->success("Successfully imported $importCount comments");
    }

    /**
     * Look up a Mastodon account ID by username
     *
     * @param string $instance The Mastodon instance URL
     * @param string $username The username to look up
     * @return string|null The account ID or null if not found
     */
    protected function lookupAccount(string $instance, string $username): ?string
    {
        $url = "$instance/api/v1/accounts/lookup?acct=$username";

        $response = $this->makeHttpRequest($url);
        if (!$response) {
            return null;
        }

        $data = json_decode($response);
        return $data->id ?? null;
    }

    /**
     * Fetch all statuses from a Mastodon account using pagination
     *
     * @param string $instance The Mastodon instance URL
     * @param string $accountId The account ID
     * @param int $limit Maximum number of statuses to fetch in total
     * @return array The statuses
     */
    protected function fetchAllStatuses(string $instance, string $accountId, int $limit): array
    {
        $allStatuses = [];
        $pageSize = min(40, $limit); // Mastodon API typically limits to 40 per request
        $maxPages = ceil($limit / $pageSize);
        $maxId = null;
        $page = 1;

        $this->cli->info("Fetching up to $limit posts in batches of $pageSize...");

        while (count($allStatuses) < $limit && $page <= $maxPages) {
            $url = "$instance/api/v1/accounts/$accountId/statuses?limit=$pageSize";

            if ($maxId) {
                $url .= "&max_id=$maxId";
            }

            $response = $this->makeHttpRequest($url);
            if (!$response) {
                break;
            }

            $statuses = json_decode($response) ?: [];
            if (empty($statuses)) {
                break; // No more statuses to fetch
            }

            $allStatuses = array_merge($allStatuses, $statuses);
            $this->cli->info("Fetched page $page with " . count($statuses) . " posts (total: " . count($allStatuses) . ")");

            // Get the ID of the last status for pagination
            $lastStatus = end($statuses);
            $maxId = $lastStatus->id;

            $page++;

            // Small delay to avoid rate limiting
            usleep(300000); // 300ms
        }

        return $allStatuses;
    }

    /**
     * Make an HTTP request
     *
     * @param string $url The URL to request
     * @return string|null The response body or null on failure
     */
    protected function makeHttpRequest(string $url): ?string
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Meh Comment System/1.0');
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        if ($httpCode >= 400 || $response === false) {
            $error = curl_error($ch);
            $this->cli->error("HTTP request failed: $error (HTTP $httpCode)");
            curl_close($ch);
            return null;
        }

        curl_close($ch);
        return $response;
    }
}
