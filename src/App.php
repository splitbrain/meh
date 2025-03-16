<?php

namespace splitbrain\meh;

use splitbrain\phpsqlite\SQLite;

class App
{
    /**
     * @var SQLite Database connection
     */
    protected $db;
    
    /**
     * @var array Configuration options
     */
    protected $config;
    
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
            'db_schema' => getenv('DB_SCHEMA') ?: 'db/',
        ];
        
        // Override with any provided config
        $this->config = array_merge($this->config, $config);
    }
    
    /**
     * Get the database connection
     * 
     * @return SQLite
     */
    public function getDatabase()
    {
        if (!$this->db) {
            $file = __DIR__ . '/../' . $this->config['db_path'];
            $schema = __DIR__ . '/../' . $this->config['db_schema'];
            $this->db = new SQLite($file, $schema);
        }
        return $this->db;
    }
    
    /**
     * Set a custom database connection (useful for testing)
     * 
     * @param SQLite $db
     * @return void
     */
    public function setDatabase(SQLite $db)
    {
        $this->db = $db;
    }
    
    /**
     * Get a configuration value
     * 
     * @param string $key Configuration key
     * @param mixed $default Default value if key doesn't exist
     * @return mixed Configuration value
     */
    public function getConfig($key, $default = null)
    {
        return isset($this->config[$key]) ? $this->config[$key] : $default;
    }
    
    /**
     * Set a configuration value
     * 
     * @param string $key Configuration key
     * @param mixed $value Configuration value
     * @return void
     */
    public function setConfig($key, $value)
    {
        $this->config[$key] = $value;
    }
}
