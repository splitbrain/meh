<?php

namespace splitbrain\meh\Tests\ApiControllers;

use PHPUnit\Framework\TestCase;
use splitbrain\meh\ApiControllers\CommentApiController;
use splitbrain\meh\App;

abstract class AbstractApiControllerTest extends TestCase
{
    protected App $app;
    private string $tempDbDir;

    protected function setUp(): void
    {
        // Create a temporary database file
        $this->tempDbDir = tempnam(sys_get_temp_dir(), 'meh_test_');
        unlink($this->tempDbDir);
        mkdir($this->tempDbDir);

        // Initialize the App with a configuration that uses the temporary database
        $_ENV['DB_PATH'] = $this->tempDbDir;
        $this->app = new App('test', null, true);

        // initialize the database
        $this->app->db()->migrate();

        // reload the app
        $this->app = new App('test');
    }

    protected function tearDown(): void
    {
        // clear environment
        unset($_ENV);

        // Close database connection
        unset($this->app);

        if (is_dir($this->tempDbDir)) {

            array_map('unlink', glob("$this->tempDbDir/*"));
            rmdir($this->tempDbDir);
        }
    }

}
