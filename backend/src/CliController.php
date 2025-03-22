<?php

namespace splitbrain\meh;

use splitbrain\phpcli\Colors;
use splitbrain\phpcli\Exception;
use splitbrain\phpcli\Options;
use splitbrain\phpcli\PSR3CLIv3;
use splitbrain\phpcli\TableFormatter;
use splitbrain\phpsqlite\SQLite;

class CliController extends PSR3CLIv3
{
    /**
     * @var App Application container
     */
    protected $app;

    /** @inheritdoc */
    protected function setup(Options $options)
    {
        $options->useCompactHelp();
        $options->setHelp('Command line tool for the meh commenting system');
        $options->registerOption('site', 'The site to operate on. Defaults to "meh"', 's', 'site');

        $options->registerCommand(
            'migrate',
            'Initialize or upgrade the database structure. Needs to be run on updates or to initialize new sites.'
        );

        $options->registerCommand(
            'config',
            "Show or edit configuration values.\n".
            "Configuration can come from defaults, the environment (or .env file) or from the database. ".
            "If no arguments are given a table with all set options is shown.\n".
            "Use the <key> and <value> arguments to set a new value in the database. ".
            "Use an empty string as value to delete a key."
        );
        $options->registerArgument('key', 'The configuration key to show or edit', false, 'config');
        $options->registerArgument('value', 'The new value to set', false, 'config');

        $options->registerCommand('disqus', 'Import comments from disqus');
        $options->registerArgument('export.xml', 'The export file to import', true, 'disqus');

        $options->registerCommand('mastodon', 'Fetch posts from a Mastodon account that link to your site and import replies as comments');
    }

    /** @inheritdoc */
    protected function main(Options $options)
    {
        $site = $options->getOpt('site', 'meh');

        if (!$options->getCmd()) {
            echo $options->help();
            return 0;
        }

        $this->app = new App($site, $this, ($options->getCmd() === 'migrate'));

        switch ($options->getCmd()) {
            case 'migrate':
                $this->migrateDatabase();
                break;
            case 'config':
                $key = $options->getArgs()[0] ?? null;
                $value = $options->getArgs()[1] ?? null;
                if ($key === null) {
                    $this->configShowAll();
                } else {
                    $this->config($key, $value);
                }
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
        return 0;
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

        if(!$this->app->conf('jwt_secret') && !$db->getOpt('jwt_secret')) {
            $this->info("Generating new JWT secret");
            $this->config('jwt_secret', bin2hex(random_bytes(16)));
        }
    }

    /**
     * Read or set a config value
     *
     * @param ?string $key
     * @param ?string $value
     * @return void
     */
    protected function config(string|null $key, string|null $value): void
    {
        $allowed = array_keys($this->app->loadConfigFromDefaults());
        if (!in_array($key, $allowed)) {
            throw new Exception("Unknown configuration key: $key", 1);
        }

        $db = $this->app->db();
        if ($value !== null) {
            if ($value === '') {
                $db->exec('DELETE FROM opt WHERE conf = ?', [$key]);
            } else {
                if($key === 'admin_password') {
                    $value = password_hash($value, PASSWORD_DEFAULT);
                }

                $db->setOpt($key, $value);
            }

        } else {
            echo $this->app->conf($key);
        }
    }

    /**
     * Display a table of all set configuration values and their sources
     *
     * @return void
     */
    protected function configShowAll(): void
    {
        $default = $this->app->loadConfigFromDefaults();
        $env = $this->app->loadConfigFromEnvironment();
        $db = $this->app->loadConfigFromDatabase();
        $all = array_merge($default, $env, $db);

        $td = new TableFormatter($this->colors);

        echo $td->format(
            ['*', '20%', '30%', '30%'],
            ['Key', 'Default', 'Environment', 'Database'],
            [Colors::C_LIGHTCYAN, Colors::C_LIGHTCYAN, Colors::C_LIGHTCYAN, Colors::C_LIGHTCYAN, Colors::C_LIGHTCYAN]
        );
        foreach ($all as $key => $value) {
            echo $td->format(
                ['*', '20%', '30%', '30%'],
                [$key, $default[$key] ?? '', $env[$key] ?? '', $db[$key] ?? ''],
                [
                    Colors::C_LIGHTBLUE,
                    ($default[$key] ?? '') === $value ? Colors::C_LIGHTGREEN : Colors::C_RESET,
                    ($env[$key] ?? '') === $value ? Colors::C_LIGHTGREEN : Colors::C_RESET,
                    ($db[$key] ?? '') === $value ? Colors::C_LIGHTGREEN : Colors::C_RESET,
                ]
            );
        }
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

        $fetcher = new MastodonFetcher($this->app);

        // First fetch posts from the account
        $this->info("Step 1: Fetching posts from Mastodon account");
        $fetcher->fetchPosts($account);

        // Then fetch replies to those posts
        $this->info("Step 2: Fetching replies to Mastodon posts");
        $fetcher->fetchReplies();
    }
}
