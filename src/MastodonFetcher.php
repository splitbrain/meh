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

        // Fetch the statuses
        $statuses = $this->fetchStatuses($instance, $accountId, $limit);
        if (empty($statuses)) {
            $this->cli->warning("No posts found for this account");
            return;
        }

        $this->cli->info("Fetched " . count($statuses) . " posts");

        // Process the statuses to find links to our site
        $matchingPosts = [];
        $db = $this->app->db();

        foreach ($statuses as $status) {
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
                var_dump($status);
            }
        }

        $this->cli->success("Found " . count($matchingPosts) . " posts linking to your site");
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
     * Fetch statuses from a Mastodon account
     *
     * @param string $instance The Mastodon instance URL
     * @param string $accountId The account ID
     * @param int $limit Maximum number of statuses to fetch
     * @return array The statuses
     */
    protected function fetchStatuses(string $instance, string $accountId, int $limit): array
    {
        $url = "$instance/api/v1/accounts/$accountId/statuses?limit=$limit";

        $response = $this->makeHttpRequest($url);
        if (!$response) {
            return [];
        }

        return json_decode($response) ?: [];
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
