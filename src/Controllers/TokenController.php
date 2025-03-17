<?php

namespace splitbrain\meh\Controllers;

use Firebase\JWT\JWT;
use splitbrain\meh\Controller;
use splitbrain\meh\HttpException;

class TokenController extends Controller
{
    /**
     * Issue a new admin token
     *
     * @param array $data Request data containing password
     * @return array The token data
     * @throws HttpException If password is missing or invalid
     */
    public function admin(array $data): array
    {
        // Check if password is provided
        if (empty($data['password'])) {
            throw new HttpException('Password is required', 400);
        }

        // Get admin password from environment
        $adminPassword = $this->app->conf('admin_password');
        if (empty($adminPassword)) {
            throw new HttpException('Admin password not configured', 500);
        }

        // Verify password
        if (!hash_equals($adminPassword, $data['password'])) {
            throw new HttpException('Invalid password', 401);
        }

        // Create token with admin scope
        $payload = [
            'iat' => time(),
            'exp' => time() + (60 * 60 * 24), // 24 hours
            'scopes' => ['admin']
        ];

        $token = JWT::encode($payload, $this->app->conf('jwt_secret'), 'HS256');

        return [
            'token' => $token,
            'expires' => date('c', $payload['exp']),
            'scopes' => $payload['scopes']
        ];
    }
}
