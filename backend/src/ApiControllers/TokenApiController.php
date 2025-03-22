<?php

namespace splitbrain\meh\ApiControllers;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use splitbrain\meh\ApiController;
use splitbrain\meh\HttpException;

class TokenApiController extends ApiController
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
        if (!password_verify($data['password'], $adminPassword)) {
            throw new HttpException('Invalid password', 401);
        }

        // Create token with admin scope
        $payload = [
            'iat' => time(),
            'sub' => bin2hex(random_bytes(16)), // Add a random subject identifier
            'scopes' => ['admin']
        ];

        $token = JWT::encode($payload, $this->app->conf('jwt_secret'), 'HS256');

        return [
            'token' => $token,
            'scopes' => $payload['scopes']
        ];
    }

    /**
     * Refresh an existing token or issue a new user token
     *
     * @param array $data Request data
     * @return array The new token data
     */
    public function refresh(array $data): array
    {
        $payload = [
            'iat' => time(),
            'sub' => bin2hex(random_bytes(16)),
            'scopes' => ['user']
        ];

        // If there's a valid token, preserve its scope and subject
        $existingPayload = $this->app->getTokenPayload(false);
        if ($existingPayload) {
            // Preserve the existing scopes and subject
            if (isset($existingPayload->scopes)) {
                $payload['scopes'] = $existingPayload->scopes;
            }
            
            if (isset($existingPayload->sub)) {
                $payload['sub'] = $existingPayload->sub;
            }
        }

        $token = JWT::encode($payload, $this->app->conf('jwt_secret'), 'HS256');

        return [
            'token' => $token,
            'scopes' => $payload['scopes']
        ];
    }
}
