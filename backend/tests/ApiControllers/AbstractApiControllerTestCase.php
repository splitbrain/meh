<?php

namespace splitbrain\meh\Tests\ApiControllers;

use PHPUnit\Framework\TestCase;
use splitbrain\meh\App;

abstract class AbstractApiControllerTestCase extends TestCase
{
    protected App $app;
    private string $tempDbDir;

    protected function setUp(): void
    {
        // Create a temporary database file
        $this->tempDbDir = tempnam(sys_get_temp_dir(), 'meh_test_');
        unlink($this->tempDbDir);
        mkdir($this->tempDbDir);

        // Initialize the App with a configuration that uses the temporary database.
        // Must use putenv() with the MEH_ prefix: App reads MEH_DB_PATH and rebuilds $_ENV
        // from getenv() in loadConfigFromEnvironment(), so a plain $_ENV assignment is lost.
        putenv('MEH_DB_PATH=' . $this->tempDbDir);
        $this->app = new App('test', null, true);

        // initialize the database
        $this->app->db()->migrate();

        // reload the app
        $this->app = new App('test');
    }

    protected function tearDown(): void
    {
        // clear environment
        putenv('MEH_DB_PATH');
        unset($_ENV);

        // Close database connection
        unset($this->app);

        if (is_dir($this->tempDbDir)) {

            array_map('unlink', glob("$this->tempDbDir/*"));
            rmdir($this->tempDbDir);
        }
    }

    /**
     * Create a token payload object
     *
     * @param array $scopes The scopes the token should have
     * @param int $relativeTime The issued at time of the token in seconds relative to now
     * @param string|null $sub The subject of the token, auto generated if null
     * @return object
     */
    protected function createTokenPayload(array $scopes = ['user'], int $relativeTime = -90, $sub = null): object
    {
        if ($sub === null) {
            $sub = bin2hex(random_bytes(10));
        }
        return (object)[
            'scopes' => $scopes,
            'iat' => time() + $relativeTime,
            'sub' => $sub
        ];
    }
}
