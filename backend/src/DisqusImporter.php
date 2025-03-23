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
     * @var \SimpleXMLElement The loaded XML document
     */
    protected $xml;

    /**
     * @var SQLite Database connection
     */
    protected $db;

    /**
     * Constructor
     *
     * @param App $app Application container
     */
    public function __construct(App $app)
    {
        $this->app = $app;
        $this->logger = $app->log();
        $this->db = $app->db();
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

        if (!$this->loadXmlFile($file)) {
            return 0;
        }

        // Count imported comments
        $count = 0;

        // Process each post in the XML
        foreach ($this->xml->xpath('//disqus:post') as $post) {
            if ($this->processPost($post)) {
                $count++;
            }
        }

        $this->logger->info("Successfully imported $count comments");
        return $count;
    }

    /**
     * Load and validate the XML file
     *
     * @param string $file Path to the XML file
     * @return bool True if file was loaded successfully
     */
    protected function loadXmlFile(string $file): bool
    {
        // Check if file exists
        if (!file_exists($file)) {
            $this->logger->error("File not found: $file");
            return false;
        }

        // Load the XML file
        $this->logger->info("Loading XML file...");
        $this->xml = simplexml_load_file($file);
        if ($this->xml === false) {
            $this->logger->error("Failed to parse XML file");
            return false;
        }

        // Register the Disqus namespace
        $this->xml->registerXPathNamespace('disqus', 'http://disqus.com');
        return true;
    }

    /**
     * Process a single post from the XML
     *
     * @param \SimpleXMLElement $post The post element to process
     * @return bool True if the post was successfully imported
     */
    protected function processPost(\SimpleXMLElement $post): bool
    {
        // Get thread information
        $postPath = $this->getPostPath($post);
        if (empty($postPath)) {
            return false;
        }

        // Extract comment data
        $commentData = $this->extractCommentData($post, $postPath);
        
        // Insert into database
        return $this->insertComment($commentData);
    }

    /**
     * Get the post path from a post element
     *
     * @param \SimpleXMLElement $post The post element
     * @return string|null The post path or null if not found
     */
    protected function getPostPath(\SimpleXMLElement $post): ?string
    {
        $thread = $post->thread;
        $thread_id = (string)$thread->attributes('dsq', true)->id;

        // Find the thread link for this post
        $threadNodes = $this->xml->xpath("//disqus:thread[@dsq:id='$thread_id']");
        
        if (empty($threadNodes) || empty($threadNodes[0]->link)) {
            $this->logger->warning("Skipping post: could not find thread link");
            return null;
        }
        
        $threadLink = (string)$threadNodes[0]->link;
        
        // Extract post URL path from the full URL
        return parse_url($threadLink, PHP_URL_PATH);
    }

    /**
     * Extract comment data from a post element
     *
     * @param \SimpleXMLElement $post The post element
     * @param string $postPath The path of the post
     * @return array The extracted comment data
     */
    protected function extractCommentData(\SimpleXMLElement $post, string $postPath): array
    {
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
        $status = $this->determineStatus($post);

        // Extract creation date
        $createdAt = isset($post->createdAt) ? (string)$post->createdAt : date('Y-m-d H:i:s');

        return [
            'post' => $postPath,
            'author' => $authorName,
            'ip' => $ipAddress,
            'email' => $authorEmail,
            'website' => $authorWebsite,
            'text' => $text,
            'html' => $html,
            'status' => $status,
            'created_at' => $createdAt
        ];
    }

    /**
     * Determine the status of a comment
     *
     * @param \SimpleXMLElement $post The post element
     * @return string The status (approved, spam, or deleted)
     */
    protected function determineStatus(\SimpleXMLElement $post): string
    {
        $status = 'approved';
        if (isset($post->isSpam) && (string)$post->isSpam === 'true') {
            $status = 'spam';
        } elseif (isset($post->isDeleted) && (string)$post->isDeleted === 'true') {
            $status = 'deleted';
        }
        return $status;
    }

    /**
     * Insert a comment into the database
     *
     * @param array $data The comment data
     * @return bool True if the comment was successfully inserted
     */
    protected function insertComment(array $data): bool
    {
        try {
            $this->db->query(
                'INSERT INTO comments 
                (post, author, ip, email, website, text, html, status, created_at) 
                VALUES 
                (?, ?, ?, ?, ?, ?, ?, ?, ?)',
                [
                    $data['post'],
                    $data['author'],
                    $data['ip'],
                    $data['email'],
                    $data['website'],
                    $data['text'],
                    $data['html'],
                    $data['status'],
                    $data['created_at']
                ]
            );
            return true;
        } catch (\Exception $e) {
            $this->logger->error("Error importing comment: " . $e->getMessage());
            return false;
        }
    }
}
