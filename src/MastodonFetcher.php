<?php

namespace splitbrain\meh;

use GuzzleHttp\Client;
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
     * @return void
     */
    public function fetchPosts(string $account): void
    {
        // Parse the account string to extract username and instance
        if (!preg_match('/^@?([^@]+)@(.+)$/', $account, $matches)) {
            $this->cli->error("Invalid account format. Use @username@instance.social");
            return;
        }

        $username = $matches[1];
        $instanceHost = $matches[2];
        $instance = "https://$instanceHost";

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
            // Skip private posts
            if (in_array($status->visibility, ['private', 'direct'])) {
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
     * Fetch replies to Mastodon threads and add them as comments
     *
     * @return void
     */
    public function fetchReplies(): void
    {
        $db = $this->app->db();

        // Get all threads from the database
        try {
            $threads = $db->queryAll('SELECT * FROM mastodon_threads ORDER BY created_at DESC');
        } catch (\Exception $e) {
            $this->cli->error("Error querying database: " . $e->getMessage());
            return;
        }

        if (empty($threads)) {
            $this->cli->warning("No Mastodon threads found in the database");
            return;
        }

        $this->cli->info("Found " . count($threads) . " Mastodon threads to check for replies");

        $totalReplies = 0;
        $importedReplies = 0;

        foreach ($threads as $thread) {
            // Extract instance from the URL
            $instance = parse_url($thread['url'], PHP_URL_HOST);
            if (!$instance) {
                $this->cli->warning("Could not determine instance from URL: " . $thread['url']);
                continue;
            }

            $instance = "https://$instance";

            // Get the status ID from the URL or URI
            $statusId = $thread['id'];

            $this->cli->info("Checking for replies to thread: " . $thread['url']);

            // Fetch the context (replies) for this status
            $context = $this->fetchStatusContext($instance, $statusId);
            if (!$context || empty($context->descendants)) {
                $this->cli->info("No replies found for this thread");
                continue;
            }

            $this->cli->info("Found " . count($context->descendants) . " replies to this thread");
            $totalReplies += count($context->descendants);

            // Process each reply
            foreach ($context->descendants as $reply) {
                // Skip if we've already imported this reply
                if ($this->isReplyImported($reply->uri)) {
                    $this->cli->info("Reply already imported: " . $reply->url);
                    continue;
                }

                // Skip non-public replies
                if (in_array($reply->visibility, ['private', 'direct'])) {
                    continue;
                }

                // Extract the text content
                $text = strip_tags($reply->content);

                // Get avatar URL
                $avatarUrl = '';
                if (isset($reply->account->avatar)) {
                    $avatarUrl = $reply->account->avatar;
                }

                // Insert into comments table
                try {
                    $commentId = $db->exec(
                        'INSERT INTO comments 
                        (post, author, website, text, html, status, created_at, avatar) 
                        VALUES 
                        (?, ?, ?, ?, ?, ?, ?, ?)',
                        [
                            $thread['post'],
                            $reply->account->acct,
                            $reply->url,
                            $text,
                            $reply->content,
                            'approved',
                            date('Y-m-d H:i:s', strtotime($reply->created_at)),
                            $avatarUrl
                        ]
                    );


                    // Record this reply in the mastodon_posts table
                    $db->query(
                        'INSERT INTO mastodon_posts (uri, comment_id) VALUES (?, ?)',
                        [$reply->uri, $commentId]
                    );

                    $importedReplies++;
                    $this->cli->success("Imported reply from " . $reply->account->acct . ": " . $reply->url);
                } catch (\Exception $e) {
                    $this->cli->error("Error importing reply: " . $e->getMessage());
                }
            }
        }

        $this->cli->success("Found $totalReplies replies in total");
        $this->cli->success("Successfully imported $importedReplies new replies as comments");
    }

    /**
     * Check if a reply has already been imported
     *
     * @param string $uri The URI of the reply
     * @return bool True if already imported, false otherwise
     */
    protected function isReplyImported(string $uri): bool
    {
        $db = $this->app->db();

        try {
            $result = $db->queryRecord(
                'SELECT uri FROM mastodon_posts WHERE uri = ?',
                [$uri]
            );

            return !empty($result);
        } catch (\Exception $e) {
            $this->cli->error("Error checking if reply is imported: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Fetch the context (replies) for a status
     *
     * @param string $instance The Mastodon instance URL
     * @param string $statusId The status ID
     * @return object|null The context object or null on failure
     */
    protected function fetchStatusContext(string $instance, string $statusId): ?object
    {
        $url = "$instance/api/v1/statuses/$statusId/context";

        $response = $this->makeHttpRequest($url);
        if (!$response) {
            return null;
        }

        return json_decode($response);
    }

    /**
     * Make an HTTP request using Guzzle
     *
     * @param string $url The URL to request
     * @return string|null The response body or null on failure
     */
    protected function makeHttpRequest(string $url): ?string
    {
        $headers = [
            'User-Agent' => 'Meh Comment System/1.0'
        ];

        // Add Authorization header if token is configured
        $token = $this->app->conf('mastodon_token');
        if (!empty($token)) {
            $headers['Authorization'] = 'Bearer ' . $token;
        }

        $client = new \GuzzleHttp\Client([
            'timeout' => 30,
            'headers' => $headers
        ]);

        try {
            $response = $client->request('GET', $url);
            return (string) $response->getBody();
        } catch (\GuzzleHttp\Exception\RequestException $e) {
            $statusCode = $e->hasResponse() ? $e->getResponse()->getStatusCode() : 'unknown';
            $this->cli->error("HTTP request failed: {$e->getMessage()} (HTTP $statusCode)");
            return null;
        } catch (\Exception $e) {
            $this->cli->error("HTTP request failed: {$e->getMessage()}");
            return null;
        }
    }
}
