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
     * @var array Mapping of Disqus IDs to our database IDs
     */
    protected $idMapping = [];

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

        // get all posts
        $posts = $this->xml->xpath('//disqus:post');

        $all = count($posts);
        $successful = 0;
        $processed = 0;

        // process each post
        foreach ($posts as $post) {
            if ($this->processPost($post)) {
                $successful++;
            }
            $processed++;
            if ($processed % 100 === 0) {
                $this->logger->info("Processed $processed of $all comments");
            }
        }

        $this->logger->info("Imported $successful of $all comments");
        return $successful;
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

        // Get Disqus ID
        $disqusId = (string)$post->attributes('dsq', true)->id;
        if (empty($disqusId)) {
            $this->logger->warning("Skipping post: missing Disqus ID");
            return false;
        }

        // Extract comment data
        $commentData = $this->extractCommentData($post, $postPath);

        // Look up parent ID if there's a parent_disqus_id
        if (!empty($commentData['parent_disqus_id'])) {
            $commentData['parent'] = $this->idMapping[$commentData['parent_disqus_id']] ?? null;
        } else {
            $commentData['parent'] = null;
        }

        // Insert into database
        $dbId = $this->insertComment($commentData);
        if ($dbId) {
            // Store mapping between Disqus ID and our database ID
            $this->idMapping[$disqusId] = $dbId;
            return true;
        }

        return false;
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

        // Store parent ID for later processing
        $parentDisqusId = null;
        if (isset($post->parent)) {
            $parentDisqusId = (string)$post->parent->attributes('dsq', true)->id;
        }

        return [
            'post' => $postPath,
            'author' => $authorName,
            'ip' => $ipAddress,
            'email' => $authorEmail,
            'website' => $authorWebsite,
            'text' => $text,
            'html' => $html,
            'status' => $status,
            'created_at' => $createdAt,
            'disqus_id' => (string)$post->attributes('dsq', true)->id,
            'parent_disqus_id' => $parentDisqusId
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
     * @return int|false The inserted comment ID or false on failure
     */
    protected function insertComment(array $data)
    {
        try {
            return $this->db->exec(
                'INSERT INTO comments 
                (post, author, ip, email, website, text, html, status, created_at, parent) 
                VALUES 
                (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
                [
                    $data['post'],
                    $data['author'],
                    $data['ip'],
                    $data['email'],
                    $data['website'],
                    $data['text'],
                    $data['html'],
                    $data['status'],
                    $data['created_at'],
                    $data['parent']
                ]
            );
        } catch (\Exception $e) {
            $this->logger->error("Error importing comment: " . $e->getMessage());
            return false;
        }
    }

}
