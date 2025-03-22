<?php

namespace splitbrain\meh\Tests\ApiControllers;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use PHPUnit\Framework\TestCase;
use splitbrain\meh\App;
use splitbrain\meh\ApiControllers\TokenApiController;
use splitbrain\meh\HttpException;

class TokenControllerTest extends AbstractApiControllerTestCase
{
    private $controller;
    private $testPassword = 'test-password';
    private $testSecret = 'test-secret';

    protected function setUp(): void
    {
        $_ENV['ADMIN_PASSWORD'] = password_hash($this->testPassword, PASSWORD_DEFAULT);
        $_ENV['JWT_SECRET'] = $this->testSecret;

        parent::setUp();

        // Create the controller with the test App
        $this->controller = new TokenApiController($this->app);
    }

    public function testAdminRequiresPassword(): void
    {
        $this->expectException(HttpException::class);
        $this->expectExceptionMessage('Password is required');
        $this->expectExceptionCode(400);

        $this->controller->admin([]);
    }

    public function testAdminRejectsInvalidPassword(): void
    {
        $this->expectException(HttpException::class);
        $this->expectExceptionMessage('Invalid password');
        $this->expectExceptionCode(401);

        $this->controller->admin(['password' => 'wrong-password']);
    }

    public function testAdminIssuesValidToken(): void
    {
        $result = $this->controller->admin(['password' => $this->testPassword]);

        // Check response structure
        $this->assertArrayHasKey('token', $result);
        $this->assertArrayHasKey('scopes', $result);
        $this->assertEquals(['admin', 'user'], $result['scopes']);

        // Verify the token is valid and contains expected data
        $decoded = JWT::decode($result['token'], new Key($this->testSecret, 'HS256'));
        $this->assertEquals(['admin', 'user'], $decoded->scopes);
        $this->assertLessThanOrEqual(time(), $decoded->iat);
    }
}
