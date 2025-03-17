<?php

namespace splitbrain\meh;

use splitbrain\phpsqlite\SQLite;

class App
{
    /**
     * @var SQLite|null Database connection
     */
    protected ?SQLite $db;

    /**
     * @var array Configuration options
     */
    protected array $config;

    /**
     * Constructor
     *
     * @param array $config Optional configuration array to override environment variables
     */
    public function __construct(array $config = [])
    {
        // Load environment variables from .env file if it exists
        if (file_exists(__DIR__ . '/../.env')) {
            $dotenv = \Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
            $dotenv->load();
        }

        // Set default configuration
        $this->config = [
            'db_path' => getenv('DB_PATH') ?: 'data/meh.sqlite',
            'db_schema' => __DIR__ . '/../db/',
        ];

        // Override with any provided config
        $this->config = array_merge($this->config, $config);

        // Check if database path is absolute or relative
        $dbdir = dirname($this->config['db_path']);
        if (!is_dir($dbdir)) {
            $dbdir = __DIR__ . '/../' . $dbdir;
            if (is_dir($dbdir)) {
                $this->config['db_path'] = __DIR__ . '/../' . $this->config['db_path'];
            }
        }

    }

    /**
     * Get the database connection
     *
     * @return SQLite
     */
    public function getDatabase(): SQLite
    {
        if (!$this->db) {
            $file = $this->config['db_path'];
            $schema = $this->config['db_schema'];
            $this->db = new SQLite($file, $schema);
        }
        return $this->db;
    }

    /**
     * Get a configuration value
     *
     * @param string $key Configuration key
     * @param mixed|null $default Default value if key doesn't exist
     * @return mixed Configuration value
     */
    public function getConfig(string $key, mixed $default = null): mixed
    {
        return $this->config[$key] ?? $default;
    }


}
