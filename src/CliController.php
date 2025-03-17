<?php

namespace splitbrain\meh;

use splitbrain\phpcli\Options;
use splitbrain\phpcli\CLI;
use splitbrain\phpsqlite\SQLite;

class CliController extends CLI
{
    /**
     * @var App Application container
     */
    protected $app;

    /**
     * Constructor
     *
     * @param App|null $app Application container
     */
    public function __construct(App $app = null)
    {
        parent::__construct();
        $this->app = $app ?: new App();
    }


    protected function setup(Options $options)
    {
        $options->setHelp('Command line tool for the meh commenting system');

        $options->registerCommand('migrate', 'Upgrade the database structures');

        $options->registerCommand('disqus', 'Import comments from disqus');
        $options->registerArgument('export.xml', 'The export file to import', true, 'disqus');
        
        $options->registerCommand('mastodon', 'Fetch posts from a Mastodon account that link to your site');
        $options->registerArgument('account', 'The Mastodon account (e.g., @user@instance.social)', true, 'mastodon');
        $options->registerOption('instance', 'Mastodon instance URL (e.g., https://mastodon.social)', 'i', true, 'mastodon');
        $options->registerOption('limit', 'Maximum number of posts to fetch (default: 100)', 'l', true, 'mastodon');
    }

    protected function main(Options $options)
    {
        switch ($options->getCmd()) {
            case 'migrate':
                $this->migrateDatabase();
                break;
            case 'disqus':
                $this->importDisqus($options->getArgs()[0]);
                break;
            case 'mastodon':
                $account = $options->getArgs()[0];
                $instance = $options->getOpt('instance');
                $limit = (int)($options->getOpt('limit') ?: 100);
                $this->fetchMastodonPosts($account, $instance, $limit);
                break;
            default:
                echo $options->help();
        }
    }

    /**
     * Get the database connection
     *
     * @return SQLite
     */
    protected function getDatabase()
    {
        return $this->app->db();
    }

    protected function migrateDatabase()
    {
        $db = $this->getDatabase();
        $db->migrate();
    }


    protected function importDisqus($file)
    {
        $this->info("Importing from $file");

        // Check if file exists
        if (!file_exists($file)) {
            $this->error("File not found: $file");
            return;
        }

        // Load the XML file
        $this->info("Loading XML file...");
        $xml = simplexml_load_file($file);
        if ($xml === false) {
            $this->error("Failed to parse XML file");
            return;
        }

        // Register the Disqus namespace
        $xml->registerXPathNamespace('disqus', 'http://disqus.com');

        // Get the database connection
        $db = $this->getDatabase();

        // Count imported comments
        $count = 0;

        // Process each post in the XML
        foreach ($xml->xpath('//disqus:post') as $post) {
            // Extract post data
            $thread = $post->thread;
            $thread_id = (string)$thread->attributes('dsq', true)->id;

            // Find the thread link for this post
            $threadNodes = $xml->xpath("//disqus:thread[@dsq:id='$thread_id']");
            $threadLink = (string)$threadNodes[0]->link;

            // Skip if we couldn't find the thread link
            if (empty($threadLink)) {
                $this->warning("Skipping post: could not find thread link");
                continue;
            }

            // Extract post URL path from the full URL
            $postPath = parse_url($threadLink, PHP_URL_PATH);

            // Extract author information
            $authorName = isset($post->author->name) ? (string)$post->author->name : 'Anonymous';
            $authorEmail = isset($post->author->email) ? (string)$post->author->email : '';
            $authorWebsite = isset($post->author->link) ? (string)$post->author->link : '';

            // Extract IP address
            $ipAddress = isset($post->ipAddress) ? (string)$post->ipAddress : '';

            // Extract message content
            $html = (string)$post->message;
            $text = strip_tags($html);

            // Determine status
            $status = 'approved';
            if (isset($post->isSpam) && (string)$post->isSpam === 'true') {
                $status = 'spam';
            } elseif (isset($post->isDeleted) && (string)$post->isDeleted === 'true') {
                $status = 'deleted';
            }

            // Extract creation date
            $createdAt = isset($post->createdAt) ? (string)$post->createdAt : date('Y-m-d H:i:s');

            // Insert into database
            try {
                $db->query(
                    'INSERT INTO comments 
                    (post, author, ip, email, website, text, html, status, created_at) 
                    VALUES 
                    (?, ?, ?, ?, ?, ?, ?, ?, ?)',
                    [
                        $postPath,
                        $authorName,
                        $ipAddress,
                        $authorEmail,
                        $authorWebsite,
                        $text,
                        $html,
                        $status,
                        $createdAt
                    ]
                );
                $count++;
            } catch (\Exception $e) {
                $this->error("Error importing comment: " . $e->getMessage());
            }
        }

        $this->success("Successfully imported $count comments");
    }
    /**
     * Fetch posts from a Mastodon account and look for links to the site
     *
     * @param string $account The Mastodon account (e.g., @user@instance.social)
     * @param string|null $instance The Mastodon instance URL
     * @param int $limit Maximum number of posts to fetch
     * @return void
     */
    protected function fetchMastodonPosts(string $account, ?string $instance = null, int $limit = 100): void
    {
        // Parse the account string to extract username and instance
        if (preg_match('/^@?([^@]+)@(.+)$/', $account, $matches)) {
            $username = $matches[1];
            $instanceHost = $matches[2];
        } else {
            // If no instance in account string, require the instance parameter
            if (empty($instance)) {
                $this->error("Invalid account format. Use @username@instance.social or provide --instance parameter");
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

        $this->info("Fetching posts from $username@$instanceHost");
        
        // First, we need to look up the account ID
        $accountId = $this->lookupMastodonAccount($instance, $username);
        if (!$accountId) {
            $this->error("Could not find Mastodon account: $username@$instanceHost");
            return;
        }
        
        $this->info("Found account ID: $accountId");
        
        // Get the site URL from config
        $siteUrl = $this->app->conf('site_url');
        $this->info("Looking for posts linking to: $siteUrl");
        
        // Fetch the statuses
        $statuses = $this->fetchMastodonStatuses($instance, $accountId, $limit);
        if (empty($statuses)) {
            $this->warning("No posts found for this account");
            return;
        }
        
        $this->info("Fetched " . count($statuses) . " posts");
        
        // Process the statuses to find links to our site
        $matchingPosts = [];
        $db = $this->getDatabase();
        
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
                    $this->success("Imported comment from Mastodon post: " . $status->url);
                } catch (\Exception $e) {
                    $this->error("Error importing comment: " . $e->getMessage());
                }
            }
        }
        
        $this->success("Found " . count($matchingPosts) . " posts linking to your site");
    }
    
    /**
     * Look up a Mastodon account ID by username
     *
     * @param string $instance The Mastodon instance URL
     * @param string $username The username to look up
     * @return string|null The account ID or null if not found
     */
    protected function lookupMastodonAccount(string $instance, string $username): ?string
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
    protected function fetchMastodonStatuses(string $instance, string $accountId, int $limit): array
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
            $this->error("HTTP request failed: $error (HTTP $httpCode)");
            curl_close($ch);
            return null;
        }
        
        curl_close($ch);
        return $response;
    }
}
