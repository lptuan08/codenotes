<?php

namespace App\Middleware;

use App\Helpers\RequestHelper;
use App\Helpers\ResponseHelper;
use Core\Jwt;
use Core\JwtPayloadRegistry;

class JwtMiddleware
{
    public function handle(): bool
    {
        JwtPayloadRegistry::clear();

        $authorizationHeader = RequestHelper::getAuthorizationHeader();
        $token = Jwt::getBearerToken($authorizationHeader);

        if ($token === null) {
            ResponseHelper::error('Missing Bearer token', 401);
            return false;
        }

        $payload = Jwt::verify($token);

        if ($payload === null) {
            ResponseHelper::error('Invalid or expired token', 401);
            return false;
        }

        JwtPayloadRegistry::register($payload);

        return true;
    }
}
