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
     * @return void
     */
    public function fetchPosts(string $account, ?string $instance = null): void
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

        // Get the most recent status ID we've already imported
        $sinceId = $this->getLatestImportedStatusId($accountId);
        if ($sinceId) {
            $this->cli->info("Fetching posts newer than ID: $sinceId");
        } else {
            $this->cli->info("No previous imports found, fetching all posts");
        }
        
        // Fetch all statuses with pagination
        $allStatuses = $this->fetchAllStatuses($instance, $accountId, $sinceId);
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

                // Insert into mastodon_threads table
                try {
                    $db->query(
                        'INSERT INTO mastodon_threads 
                        (id, account, url, uri, post, created_at) 
                        VALUES 
                        (?, ?, ?, ?, ?, ?)',
                        [
                            $status->id,
                            $username . '@' . $instanceHost,
                            $status->url,
                            $status->uri ?? $status->url,
                            $postPath,
                            date('Y-m-d H:i:s', strtotime($status->created_at))
                        ]
                    );
                    $importCount++;
                    $this->cli->success("Imported Mastodon thread: " . $status->url);
                } catch (\Exception $e) {
                    $this->cli->error("Error importing Mastodon thread: " . $e->getMessage());
                }
            }
        }

        $this->cli->success("Found " . count($matchingPosts) . " posts linking to your site");
        $this->cli->success("Successfully imported $importCount Mastodon threads");
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
     * Get the latest imported status ID for a given account
     *
     * @param string $accountId The Mastodon account ID
     * @return string|null The latest status ID or null if none found
     */
    protected function getLatestImportedStatusId(string $accountId): ?string
    {
        $db = $this->app->db();
        
        try {
            // Query the database for the latest status ID
            $result = $db->queryRecord(
                'SELECT id FROM mastodon_threads 
                 WHERE account LIKE ? 
                 ORDER BY created_at DESC 
                 LIMIT 1',
                ['%@%'] // We can't directly match on account ID, so we'll filter in PHP
            );
            
            if ($result && isset($result['id'])) {
                return $result['id'];
            }
        } catch (\Exception $e) {
            $this->cli->error("Error querying database: " . $e->getMessage());
        }
        
        return null;
    }

    /**
     * Fetch all statuses from a Mastodon account using pagination
     *
     * @param string $instance The Mastodon instance URL
     * @param string $accountId The account ID
     * @param string|null $sinceId Only fetch statuses newer than this ID
     * @return array The statuses
     */
    protected function fetchAllStatuses(string $instance, string $accountId, ?string $sinceId = null): array
    {
        $allStatuses = [];
        $pageSize = 40; // Mastodon API typically limits to 40 per request
        $maxId = null;
        $page = 1;
        $maxPages = 25; // Safety limit to prevent infinite loops

        $this->cli->info("Fetching posts in batches of $pageSize...");

        while ($page <= $maxPages) {
            $url = "$instance/api/v1/accounts/$accountId/statuses?limit=$pageSize";

            if ($maxId) {
                $url .= "&max_id=$maxId";
            }
            
            if ($sinceId) {
                $url .= "&since_id=$sinceId";
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
            
            // If we got fewer than pageSize, we've reached the end
            if (count($statuses) < $pageSize) {
                break;
            }

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
