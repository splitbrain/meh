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

        $options->registerCommand('mastodon', 'Fetch posts from a Mastodon account that link to your site and import replies as comments');
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
                $this->fetchMastodon();
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
     * Fetch posts from a Mastodon account, look for links to the site,
     * and import replies as comments
     *
     * @return void
     */
    protected function fetchMastodon(): void
    {
        $account = $this->app->conf('mastodon_account');
        if (empty($account)) {
            $this->error("No Mastodon account configured. Set MASTODON_ACCOUNT in your .env file.");
            return;
        }
        
        $fetcher = new MastodonFetcher($this, $this->app);
        
        // First fetch posts from the account
        $this->info("Step 1: Fetching posts from Mastodon account");
        $fetcher->fetchPosts($account);
        
        // Then fetch replies to those posts
        $this->info("Step 2: Fetching replies to Mastodon posts");
        $fetcher->fetchReplies();
    }
}
