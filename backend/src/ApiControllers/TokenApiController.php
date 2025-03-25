<?php

namespace splitbrain\meh\ApiControllers;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Random\RandomException;
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
     * @throws RandomException If random bytes cannot be generated (should not happen)
     *
     */
    public function admin(array $data): array
    {
        // Check if password is provided
        if (empty($data['password'])) {
            throw new HttpException('`nopass` Password is required', 400);
        }

        // Get admin password from environment
        $adminPassword = $this->app->conf('admin_password');
        if (empty($adminPassword)) {
            throw new HttpException('`noadmin` Admin password not configured', 500);
        }

        // Verify password
        if (!password_verify((string) $data['password'], (string) $adminPassword)) {
            throw new HttpException('`badpass` Invalid password', 401);
        }

        // Create token with admin and user scopes
        $payload = [
            'iat' => time(),
            'sub' => bin2hex(random_bytes(16)), // Add a random subject identifier
            'scopes' => ['admin', 'user'] // Admin tokens should also have user scope
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
     * @throws RandomException If random bytes cannot be generated (should not happen)
     */
    public function refresh(array $data): array
    {
        $payload = [
            'iat' => time(),
            'sub' => bin2hex(random_bytes(16)),
            'scopes' => ['user']
        ];

        // If there's a valid token, preserve its scope and subject
        try {
            $existingPayload = $this->tokenPayload;

            // Preserve the existing scopes and subject
            if (isset($existingPayload->scopes)) {
                $payload['scopes'] = $existingPayload->scopes;
            }

            if (isset($existingPayload->sub)) {
                $payload['sub'] = $existingPayload->sub;
            }
        } catch (\Exception) {
            // No valid token, issue a new one
        }

        $token = JWT::encode($payload, $this->app->conf('jwt_secret'), 'HS256');

        return [
            'token' => $token,
            'scopes' => $payload['scopes']
        ];
    }
}
