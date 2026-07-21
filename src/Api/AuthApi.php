<?php

declare(strict_types=1);

namespace ChuckBartowski\PleskSdk\Api;

use ChuckBartowski\PleskSdk\Response\ApiResponse;

final class AuthApi extends AbstractApi
{
    public function createKey(?string $ip = null, ?string $ttl = null, ?string $description = null, ?string $login = null): ApiResponse
    {
        return $this->post('/auth/keys', array_filter([
            'ip' => $ip,
            'ttl' => $ttl,
            'description' => $description,
            'login' => $login,
        ]));
    }

    public function deleteKey(string $key): ApiResponse
    {
        return $this->delete('/auth/keys/'.rawurlencode($key));
    }
}
