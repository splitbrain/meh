<?php

namespace splitbrain\meh;

use Psr\Log\LoggerInterface;
use splitbrain\phpsqlite\SQLite;

class DisqusImporter
{
    /**
     * @var App Application container
     */
    protected $app;

    /**
     * @var LoggerInterface Logger
     */
    protected $logger;

    /**
     * Constructor
     *
     * @param App $app Application container
     */
    public function __construct(App $app)
    {
        $this->app = $app;
        $this->logger = $app->log();
    }

    /**
     * Import comments from a Disqus XML export file
     *
     * @param string $file Path to the Disqus XML export file
     * @return int Number of imported comments
     */
    public function import(string $file): int
    {
        $this->logger->info("Importing from $file");

        // Check if file exists
        if (!file_exists($file)) {
            $this->logger->error("File not found: $file");
            return 0;
        }

        // Load the XML file
        $this->logger->info("Loading XML file...");
        $xml = simplexml_load_file($file);
        if ($xml === false) {
            $this->logger->error("Failed to parse XML file");
            return 0;
        }

        // Register the Disqus namespace
        $xml->registerXPathNamespace('disqus', 'http://disqus.com');

        // Get the database connection
        $db = $this->app->db();

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
                $this->logger->warning("Skipping post: could not find thread link");
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
                $this->logger->error("Error importing comment: " . $e->getMessage());
            }
        }

        $this->logger->info("Successfully imported $count comments");
        return $count;
    }
}
