<?php

declare(strict_types=1);

namespace ChuckBartowski\PleskSdk\Api;

use ChuckBartowski\PleskSdk\Response\ApiResponse;

final class FtpApi extends AbstractApi
{
    public function list(?string $domain = null): ApiResponse
    {
        return $this->get('/ftpusers', array_filter(['domain' => $domain]));
    }

    public function create(string $name, string $password, string $domain, ?string $home = null): ApiResponse
    {
        return $this->post('/ftpusers', array_filter([
            'name' => $name,
            'password' => $password,
            'parent_domain' => ['name' => $domain],
            'home' => $home,
        ], static fn (mixed $v): bool => null !== $v));
    }

    public function update(string $name, array $properties): ApiResponse
    {
        return $this->put('/ftpusers/'.rawurlencode($name), $properties);
    }

    public function remove(string $name): ApiResponse
    {
        return $this->delete('/ftpusers/'.rawurlencode($name));
    }
}
