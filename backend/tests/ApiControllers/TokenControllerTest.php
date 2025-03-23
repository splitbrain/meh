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
        $_ENV['ADMIN_PASSWORD'] = password_hash((string) $this->testPassword, PASSWORD_DEFAULT);
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
    
    public function testRefreshCreatesNewUserToken(): void
    {
        $result = $this->controller->refresh([]);
        
        // Check response structure
        $this->assertArrayHasKey('token', $result);
        $this->assertArrayHasKey('scopes', $result);
        $this->assertEquals(['user'], $result['scopes']);
        
        // Verify the token is valid and contains expected data
        $decoded = JWT::decode($result['token'], new Key($this->testSecret, 'HS256'));
        $this->assertEquals(['user'], $decoded->scopes);
        $this->assertLessThanOrEqual(time(), $decoded->iat);
        $this->assertNotEmpty($decoded->sub);
    }
    
    public function testRefreshPreservesExistingTokenScopes(): void
    {
        // Create a controller with an admin token payload
        $adminPayload = $this->createTokenPayload(['admin', 'user']);
        $controllerWithToken = new TokenApiController($this->app, $adminPayload);
        
        // Refresh the token
        $result = $controllerWithToken->refresh([]);
        
        // Check that admin scope was preserved
        $this->assertArrayHasKey('token', $result);
        $this->assertArrayHasKey('scopes', $result);
        $this->assertEquals(['admin', 'user'], $result['scopes']);
        
        // Verify the token contains the preserved scopes
        $decoded = JWT::decode($result['token'], new Key($this->testSecret, 'HS256'));
        $this->assertEquals(['admin', 'user'], $decoded->scopes);
    }
    
    public function testRefreshPreservesExistingTokenSubject(): void
    {
        // Create a controller with a token payload that has a specific subject
        $specificSubject = 'test-subject-123';
        $payload = $this->createTokenPayload(['user'], -90, $specificSubject);
        $controllerWithToken = new TokenApiController($this->app, $payload);
        
        // Refresh the token
        $result = $controllerWithToken->refresh([]);
        
        // Verify the token contains the preserved subject
        $decoded = JWT::decode($result['token'], new Key($this->testSecret, 'HS256'));
        $this->assertEquals($specificSubject, $decoded->sub);
    }
}
