<?php

namespace splitbrain\meh;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use splitbrain\phpsqlite\SQLite;

class App
{
    /** @var string the site this app is running for */
    protected string $site = 'meh';

    /**
     * @var SQLite|null Database connection
     */
    protected ?SQLite $db = null;

    /**
     * @var array Configuration options
     */
    protected array $config;

    /**
     * @var LoggerInterface Logger
     */
    protected LoggerInterface $logger;

    /**
     * @var CommentUtils|null Comment utilities
     */
    protected ?CommentUtils $commentUtils = null;

    /**
     * Constructor
     *
     * @param string $site Site name
     * @param LoggerInterface|null $logger Optional logger instance
     * @param bool $init If true, initialize a new database
     */
    public function __construct(string $site = 'meh', LoggerInterface|null $logger = null, $init = false)
    {
        if ($logger instanceof LoggerInterface) {
            $this->logger = $logger;
        } else {
            $this->logger = new NullLogger();
        }

        $this->config = array_merge(
            $this->loadConfigFromDefaults(),
            $this->loadConfigFromEnvironment(),
        );

        if(!preg_match('/^[a-z0-9_\-]+$/', $site)) {
            throw new \Exception('Invalid site name');
        }

        $this->config['db_file'] = $this->config['db_path'] . '/' . $site . '.sqlite';

        // unless init is set, we expect the database to exist, then load configs from it
        if (!$init) {
            if (!file_exists($this->config['db_file'])) {
                throw new \Exception('No database for site found.');
            }

            $this->config = array_merge(
                $this->config,
                $this->loadConfigFromDatabase()
            );
        }
    }

    public function loadConfigFromDefaults(): array
    {
        return [
            'db_path' => $this->resolveDBPath('data/'),
            'jwt_secret' => '',
            'admin_password' => '',
            'site_url' => 'http://localhost:8000',
            'mastodon_account' => '',
            'mastodon_token' => '',
            'gravatar_fallback' => 'initials',
            'gravatar_rating' => 'g',
            'notify_email' => '',
            'smtp_host' => 'localhost',
            'smtp_port' => 25,
            'smtp_encryption' => '',
            'smtp_user' => '',
            'smtp_pass' => '',
        ];
    }

    public function loadConfigFromDatabase(): array
    {
        $db = $this->db();
        return $db->queryKeyValueList("SELECT conf, val FROM opt WHERE conf != 'dbversion'");
    }

    public function loadConfigFromEnvironment(): array
    {
        // Load environment variables from .env file if it exists
        if (file_exists(__DIR__ . '/../../.env')) {
            $dotenv = \Dotenv\Dotenv::createImmutable(__DIR__ . '/../..');
            $dotenv->load();
        }

        $config = [];
        foreach (array_keys(self::loadConfigFromDefaults()) as $key) {
            $ekey = strtoupper($key);
            if (isset($_ENV[$ekey])) {
                $config[$key] = $_ENV[$ekey];
            }
        }

        if (isset($config['db_path'])) {
            $config['db_path'] = $this->resolveDBPath($config['db_path']);
        }

        return $config;
    }

    public function resolveDBPath(string $path): string
    {
        $path = rtrim($path, '/');

        if (!is_dir($path) || !file_exists($path)) {
            $dbdir = __DIR__ . '/../../' . $path;
            if (is_dir($dbdir) && file_exists($dbdir)) {
                $path = $dbdir;
            }
        }
        return $path;
    }

    /**
     * Get the database connection
     *
     * @return SQLite
     */
    public function db(): SQLite
    {
        if (!$this->db) {
            $file = $this->config['db_file'];
            $schema = __DIR__ . '/../db/';
            $this->log()->info("Opening database $file");
            $this->db = new SQLite($file, $schema);
        }
        return $this->db;
    }

    /**
     * Get the comment utilities
     *
     * @return CommentUtils
     */
    public function commentUtils(): CommentUtils
    {
        if (!$this->commentUtils) {
            $this->commentUtils = new CommentUtils($this);
        }
        return $this->commentUtils;
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
     * Get the logger
     *
     * @return LoggerInterface
     */
    public function log(): LoggerInterface
    {
        return $this->logger;
    }

    /**
     * Set the logger
     *
     * @param LoggerInterface $logger
     */
    public function setLogger(LoggerInterface $logger): void
    {
        $this->logger = $logger;
    }

    /**
     * Get the token payload from the Authorization header
     *
     * @param bool $throwOnError Whether to throw an exception if the token is invalid
     * @return object|null The token payload or null if no valid token
     * @throws HttpException If the token is invalid and $throwOnError is true
     */
    public function getTokenPayload(bool $throwOnError = true): ?object
    {
        // get bearer token
        $token = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
        if (!preg_match('/^Bearer (.+)$/', (string)$token, $matches)) {
            if ($throwOnError) {
                throw new HttpException('No valid token given', 401);
            }
            return null;
        }
        
        $token = $matches[1];

        // decode token
        try {
            return JWT::decode($token, new Key($this->conf('jwt_secret'), 'HS256'));
        } catch (\Exception $e) {
            if ($throwOnError) {
                throw new HttpException('Invalid token', 401, $e);
            }
            return null;
        }
    }

    /**
     * Check if the user has the required scopes
     *
     * @param string|string[] $required list of or single required scope(s)
     * @throws HttpException
     */
    public function checkScopes(array|string $required): void
    {
        $payload = $this->getTokenPayload();
        
        // Check if scopes property exists
        if (!isset($payload->scopes)) {
            throw new HttpException('Token missing scopes', 401);
        }
        
        // check if required scopes are present
        foreach ((array)$required as $scope) {
            if (!in_array($scope, $payload->scopes)) {
                throw new HttpException('Missing required scope', 403);
            }
        }
    }
}
