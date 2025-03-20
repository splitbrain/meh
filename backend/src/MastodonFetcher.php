<?php

namespace splitbrain\meh;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Exception\RequestException;
use splitbrain\phpcli\CLI;

/**
 * Class to handle fetching and processing posts from Mastodon
 */
class MastodonFetcher
{
    /** @var App Application container */
    protected $app;

    /** @var string Site URL from configuration */
    protected $siteUrl;

    /**
     * Constructor
     *
     * @param App $app Application container
     */
    public function __construct(App $app)
    {
        $this->app = $app;
        $this->siteUrl = $this->app->conf('site_url');
    }

    /**
     * Fetch posts from a Mastodon account and look for links to the site
     *
     * @param string $account The Mastodon account (e.g., @user@instance.social)
     * @return void
     */
    public function fetchPosts(string $account): void
    {
        // Parse the account string
        [$username, $instanceHost] = $this->parseAccount($account);
        if (!$username || !$instanceHost) {
            return;
        }

        $instance = "https://$instanceHost";
        $this->app->log()->info("Fetching posts from $username@$instanceHost");

        // Look up the account ID
        $accountId = $this->lookupAccount($instance, $username);
        if (!$accountId) {
            $this->app->log()->error("Could not find Mastodon account: $username@$instanceHost");
            return;
        }

        $this->app->log()->info("Found account ID: $accountId");
        $this->app->log()->info("Looking for posts linking to: {$this->siteUrl}");

        // Get the most recent status ID we've already imported
        $sinceId = $this->getLatestImportedStatusId($account);
        if ($sinceId) {
            $this->app->log()->info("Fetching posts newer than ID: $sinceId");
        } else {
            $this->app->log()->info("No previous imports found, fetching all posts");
        }

        // Fetch all statuses
        $allStatuses = $this->fetchAllStatuses($instance, $accountId, $sinceId);
        if (empty($allStatuses)) {
            $this->app->log()->warning("No posts found for this account");
            return;
        }

        $this->app->log()->info("Fetched " . count($allStatuses) . " posts");

        // Process and import matching posts
        $this->processAndImportPosts($allStatuses, $account, $username, $instanceHost);
    }

    /**
     * Parse a Mastodon account string into username and instance
     *
     * @param string $account The account string (e.g., @user@instance.social)
     * @return array Array containing [username, instanceHost] or [null, null] if invalid
     */
    protected function parseAccount(string $account): array
    {
        if (!preg_match('/^@?([^@]+)@(.+)$/', $account, $matches)) {
            $this->app->log()->error("Invalid account format. Use @username@instance.social");
            return [null, null];
        }

        return [$matches[1], $matches[2]];
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
     * @param string $account The Mastodon account
     * @return string|null The latest status ID or null if none found
     */
    protected function getLatestImportedStatusId(string $account): ?string
    {
        $db = $this->app->db();

        try {
            $result = $db->queryRecord(
                'SELECT id FROM mastodon_threads 
                 WHERE account = ? 
                 ORDER BY created_at DESC 
                 LIMIT 1',
                [$account]
            );

            return $result['id'] ?? null;
        } catch (\Exception $e) {
            $this->app->log()->error("Error querying database: " . $e->getMessage());
            return null;
        }
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
        $pageSize = 40;
        $maxId = null;
        $page = 1;
        $maxPages = 25;

        $this->app->log()->info("Fetching posts in batches of $pageSize...");

        while ($page <= $maxPages) {
            $url = $this->buildStatusesUrl($instance, $accountId, $pageSize, $maxId, $sinceId);
            $response = $this->makeHttpRequest($url);

            if (!$response) {
                break;
            }

            $statuses = json_decode($response) ?: [];
            if (empty($statuses)) {
                break;
            }

            $allStatuses = array_merge($allStatuses, $statuses);
            $this->app->log()->info("Fetched page $page with " . count($statuses) . " posts (total: " . count($allStatuses) . ")");

            // Check if we've reached the end
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
     * Build the URL for fetching statuses
     *
     * @param string $instance The Mastodon instance URL
     * @param string $accountId The account ID
     * @param int $limit Number of statuses to fetch
     * @param string|null $maxId Only fetch statuses older than this ID
     * @param string|null $sinceId Only fetch statuses newer than this ID
     * @return string The URL
     */
    protected function buildStatusesUrl(string $instance, string $accountId, int $limit, ?string $maxId = null, ?string $sinceId = null): string
    {
        $url = "$instance/api/v1/accounts/$accountId/statuses?limit=$limit";

        if ($maxId) {
            $url .= "&max_id=$maxId";
        }

        if ($sinceId) {
            $url .= "&min_id=$sinceId";
        }

        return $url;
    }

    /**
     * Process statuses and import matching posts
     *
     * @param array $statuses The statuses to process
     * @param string $account The full Mastodon account
     * @param string $username The username part
     * @param string $instanceHost The instance host
     * @return void
     */
    protected function processAndImportPosts(array $statuses, string $account, string $username, string $instanceHost): void
    {
        $matchingPosts = [];
        $importCount = 0;
        $db = $this->app->db();

        foreach ($statuses as $status) {
            // Skip private posts
            if (in_array($status->visibility, ['private', 'direct'])) {
                continue;
            }

            $postPath = $this->findLinkToSite($status);
            if (!$postPath) {
                continue;
            }

            $matchingPosts[] = [
                'id' => $status->id,
                'url' => $status->url,
                'created_at' => $status->created_at,
                'content' => strip_tags((string)$status->content),
                'post_path' => $postPath,
                'author' => $username . '@' . $instanceHost
            ];

            // Import the thread
            try {
                $db->query(
                    'INSERT INTO mastodon_threads 
                    (id, account, url, uri, post, created_at) 
                    VALUES 
                    (?, ?, ?, ?, ?, ?)',
                    [
                        $status->id,
                        $account,
                        $status->url,
                        $status->uri ?? $status->url,
                        $postPath,
                        date('Y-m-d H:i:s', strtotime((string)$status->created_at))
                    ]
                );
                $importCount++;
                $this->app->log()->notice("Imported Mastodon thread: " . $status->url);
            } catch (\Exception $e) {
                $this->app->log()->error("Error importing Mastodon thread: " . $e->getMessage());
            }
        }

        $this->app->log()->notice("Found " . count($matchingPosts) . " posts linking to your site");
        $this->app->log()->notice("Successfully imported $importCount Mastodon threads");
    }

    /**
     * Find a link to the site in a status
     *
     * @param object $status The status to check
     * @return string|null The post path if found, null otherwise
     */
    protected function findLinkToSite(object $status): ?string
    {
        // Check content for links
        if (isset($status->content)) {
            if (preg_match_all('/<a[^>]+href="([^"]+)"[^>]*>/i', $status->content, $matches)) {
                foreach ($matches[1] as $url) {
                    if (str_starts_with($url, $this->siteUrl)) {
                        return parse_url($url, PHP_URL_PATH);
                    }
                }
            }
        }

        // Check card links if available
        if (isset($status->card) && isset($status->card->url)) {
            $cardUrl = $status->card->url;
            if (str_starts_with($cardUrl, $this->siteUrl)) {
                return parse_url($cardUrl, PHP_URL_PATH);
            }
        }

        return null;
    }

    /**
     * Fetch replies to Mastodon threads and add them as comments
     *
     * @return void
     */
    public function fetchReplies(): void
    {
        $threads = $this->getThreadsFromDatabase();
        if (empty($threads)) {
            return;
        }

        $this->app->log()->info("Found " . count($threads) . " Mastodon threads to check for replies");

        $totalReplies = 0;
        $importedReplies = 0;

        foreach ($threads as $thread) {
            $instance = $this->getInstanceFromUrl($thread['url']);
            if (!$instance) {
                continue;
            }

            $statusId = $thread['id'];
            $this->app->log()->info("Checking for replies to thread: " . $thread['url']);

            // Fetch the context (replies) for this status
            $context = $this->fetchStatusContext($instance, $statusId);
            if (!$context || empty($context->descendants)) {
                $this->app->log()->info("No replies found for this thread");
                continue;
            }

            $this->app->log()->info("Found " . count($context->descendants) . " replies to this thread");
            $totalReplies += count($context->descendants);

            // Process each reply
            foreach ($context->descendants as $reply) {
                $importResult = $this->processAndImportReply($reply, $thread, $instance);
                if ($importResult) {
                    $importedReplies++;
                }
            }
        }

        $this->app->log()->notice("Found $totalReplies replies in total");
        $this->app->log()->notice("Successfully imported $importedReplies new replies as comments");
    }

    /**
     * Get all threads from the database
     *
     * @return array The threads
     */
    protected function getThreadsFromDatabase(): array
    {
        $db = $this->app->db();

        try {
            return $db->queryAll('SELECT * FROM mastodon_threads ORDER BY created_at DESC');
        } catch (\Exception $e) {
            $this->app->log()->error("Error querying database: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get the instance from a URL
     *
     * @param string $url The URL
     * @return string|null The instance URL or null if not found
     */
    protected function getInstanceFromUrl(string $url): ?string
    {
        $host = parse_url($url, PHP_URL_HOST);
        if (!$host) {
            $this->app->log()->warning("Could not determine instance from URL: " . $url);
            return null;
        }

        return "https://$host";
    }

    /**
     * Process and import a reply
     *
     * @param object $reply The reply to process
     * @param array $thread The thread the reply belongs to
     * @param string $instance The instance URL
     * @return bool True if imported, false otherwise
     */
    protected function processAndImportReply(object $reply, array $thread, string $instance): bool
    {
        // Skip if already imported
        if ($this->isReplyImported($reply->uri)) {
            $this->app->log()->info("Reply already imported: " . $reply->url);
            return false;
        }

        // Skip non-public replies
        if (in_array($reply->visibility, ['private', 'direct'])) {
            return false;
        }

        $db = $this->app->db();
        $html = $reply->content;
        $text = strip_tags($reply->content);
        $avatarUrl = $reply->account->avatar_static ?? $reply->account->avatar_static ?? '';
        $account = $this->formatAccount($reply->account->acct, $instance);
        $author = $reply->account->display_name ?? $account;

        if ($reply->media_attachments) {
            $html .= '<div class="media-attachments">';
            foreach ($reply->media_attachments as $file) {
                $html .= '<a href="' . $file->url . '" target="_blank" rel="noopener noreferrer" class="type-' . $file->type . '">';
                if ($file->type != 'image') {
                    $html .= '<img src="' . $file->preview_url . '" alt="' . htmlspecialchars($file->description ?? '') . '">';
                } else {
                    $html .= 'Attachment';
                }
                $html .= '</a>';
            }
            $html .= '</div>';
        }

        try {
            // Insert into comments table
            $commentId = $db->exec(
                'INSERT INTO comments 
                (post, author, email, website, text, html, status, created_at, avatar) 
                VALUES 
                (?, ?, ?, ?, ?, ?, ?, ?, ?)',
                [
                    $thread['post'],
                    $author,
                    $account,
                    $reply->url,
                    $text,
                    $html,
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

            $this->app->log()->notice("Imported reply from " . $reply->account->acct . ": " . $reply->url);
            return true;
        } catch (\Exception $e) {
            $this->app->log()->error("Error importing reply: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Format an account name with instance if needed
     *
     * @param string $account The account name
     * @param string $instance The instance URL
     * @return string The formatted account name
     */
    protected function formatAccount(string $account, string $instance): string
    {
        $account = ltrim($account, '@');
        if (!str_contains($account, '@')) {
            $host = parse_url($instance, PHP_URL_HOST);
            $account .= '@' . $host;
        }
        return '@' . $account;
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
            $this->app->log()->error("Error checking if reply is imported: " . $e->getMessage());
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

        $client = new Client([
            'timeout' => 30,
            'headers' => $headers
        ]);

        try {
            $response = $client->request('GET', $url);
            return (string)$response->getBody();
        } catch (RequestException $e) {
            $statusCode = $e->hasResponse() ? $e->getResponse()->getStatusCode() : 'unknown';
            $this->app->log()->error("HTTP request failed: {$e->getMessage()} (HTTP $statusCode)");
            return null;
        } catch (\Exception $e) {
            $this->app->log()->error("HTTP request failed: {$e->getMessage()}");
            return null;
        }
    }
}
