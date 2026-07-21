<?php

declare(strict_types=1);

namespace ChuckBartowski\PleskSdk\Api;

use ChuckBartowski\PleskSdk\Response\ApiResponse;

final class DatabaseApi extends AbstractApi
{
    public function list(?string $domain = null): ApiResponse
    {
        return $this->get('/databases', array_filter(['domain' => $domain]));
    }

    public function create(string $name, string $domain, string $type = 'mysql', ?int $serverId = null): ApiResponse
    {
        return $this->post('/databases', array_filter([
            'name' => $name,
            'type' => $type,
            'parent_domain' => ['name' => $domain],
            'server_id' => $serverId,
        ], static fn (mixed $v): bool => null !== $v));
    }

    public function remove(int $id): ApiResponse
    {
        return $this->delete('/databases/'.$id);
    }

    public function users(?int $databaseId = null): ApiResponse
    {
        return $this->get('/dbusers', array_filter(['dbId' => $databaseId]));
    }

    public function createUser(string $login, string $password, int $databaseId): ApiResponse
    {
        return $this->post('/dbusers', [
            'login' => $login,
            'password' => $password,
            'database_id' => $databaseId,
        ]);
    }

    public function removeUser(int $id): ApiResponse
    {
        return $this->delete('/dbusers/'.$id);
    }
}
