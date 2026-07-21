<?php

declare(strict_types=1);

namespace ChuckBartowski\PleskSdk\Api;

use ChuckBartowski\PleskSdk\Response\ApiResponse;

final class DomainApi extends AbstractApi
{
    public function list(array $filters = []): ApiResponse
    {
        return $this->get('/domains', $filters);
    }

    public function find(int $id): ApiResponse
    {
        return $this->get('/domains/'.$id);
    }

    public function create(string $name, array $options = []): ApiResponse
    {
        return $this->post('/domains', array_merge($options, ['name' => $name]));
    }

    public function update(int $id, array $properties): ApiResponse
    {
        return $this->put('/domains/'.$id, $properties);
    }

    public function remove(int $id): ApiResponse
    {
        return $this->delete('/domains/'.$id);
    }

    public function owner(int $id): ApiResponse
    {
        return $this->get(sprintf('/domains/%d/client', $id));
    }

    public function status(int $id): ApiResponse
    {
        return $this->get(sprintf('/domains/%d/status', $id));
    }

    public function suspend(string $name): ApiResponse
    {
        return $this->cli('domain', ['--off', $name]);
    }

    public function activate(string $name): ApiResponse
    {
        return $this->cli('domain', ['--on', $name]);
    }
}
