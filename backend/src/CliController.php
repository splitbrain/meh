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
            'Initialize or upgrade the database structure. Needs to be run to initialize new sites.'
        );

        $options->registerCommand(
            'migrate-all',
            'Upgrade the database structure for all existing sites. Should be run after updating the code.'
        );

        $options->registerCommand(
            'config',
            "Show or edit configuration values.\n" .
            "Configuration can come from defaults, the environment (or .env file) or from the database. " .
            "If no arguments are given a table with all set options is shown.\n" .
            "Use the <key> and <value> arguments to set a new value in the database. " .
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

        $this->app = new App($site, $this, str_starts_with($options->getCmd(), 'migrate'));

        switch ($options->getCmd()) {
            case 'migrate':
                $this->migrateSite($site);
                break;
            case 'migrate-all':
                $this->migrateAll();
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
     * Initialize or upgrade the database structure
     *
     * @param string $site
     * @return void
     * @throws \Random\RandomException
     */
    protected function migrateSite(string $site): void
    {
        $this->app = new App($site, $this, true);
        $db = $this->app->db();
        $db->migrate();

        if (!$this->app->conf('jwt_secret') && !$db->getOpt('jwt_secret')) {
            $this->info("Generating new JWT secret");
            $this->config('jwt_secret', bin2hex(random_bytes(16)));
        }
    }

    /**
     * Upgrade the database structure for all existing sites
     *
     * @return void
     */
    protected function migrateAll(): void
    {
        $dbpath = $this->app->conf('db_path');
        $sites = glob("$dbpath/*.sqlite");
        foreach ($sites as $site) {
            $site = basename($site, '.sqlite');
            $this->migrateSite($site);
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
        $disallowed = ['db_path', 'env'];
        if (in_array($key, $disallowed)) {
            throw new Exception("$key may only be set via environment", 1);
        }

        $db = $this->app->db();
        if ($value !== null) {
            if ($value === '') {
                $db->exec('DELETE FROM opt WHERE conf = ?', [$key]);
            } else {
                if ($key === 'admin_password') {
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
        $importer = new DisqusImporter($this->app, $this);
        $count = $importer->import($file);
        if ($count > 0) {
            $this->success("Successfully imported $count comments");
        }
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
