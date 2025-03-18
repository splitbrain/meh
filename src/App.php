<?php

namespace splitbrain\meh;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use splitbrain\phpsqlite\SQLite;

class App
{
    /**
     * @var SQLite|null Database connection
     */
    protected ?SQLite $db = null;

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
            'db_path' => $_ENV['DB_PATH'] ?? 'data/meh.sqlite',
            'db_schema' => __DIR__ . '/../db/',
            'jwt_secret' => $_ENV['JWT_SECRET'] ?? 'not very secret', # FIXME we probably want a default only for testing
            'admin_password' => $_ENV['ADMIN_PASSWORD'] ?? '',
            'site_url' => $_ENV['SITE_URL'] ?? 'http://localhost:8000',
            'mastodon_account' => $_ENV['MASTODON_ACCOUNT'] ?? '',
            'mastodon_token' => $_ENV['MASTODON_TOKEN'] ?? '',
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
    public function db(): SQLite
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
    public function conf(string $key, mixed $default = null): mixed
    {
        return $this->config[$key] ?? $default;
    }

    /**
     * Check if the user has the required scopes
     *
     * @param string|string[] $required list of or single required scope(s)
     * @throws HttpException
     */
    public function checkScopes(array|string $required): void
    {
        // get bearer token
        $token = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
        if (preg_match('/^Bearer (.+)$/', $token, $matches)) {
            $token = $matches[1];
        } else {
            throw new HttpException('No valid token given', 401);
        }

        // get scopes from token
        $jwt = new JWT();
        try {
            $scopes = $jwt->decode($token, new Key($this->conf('jwt_secret'), 'HS256'))->scopes;
        } catch (\Exception $e) {
            throw new HttpException('Invalid token', 401, $e);
        }

        // check if required scopes are present
        foreach ((array)$required as $scope) {
            if (!in_array($scope, $scopes)) {
                throw new HttpException('Missing required scope', 403);
            }
        }
    }
}
