<?php

declare(strict_types=1);

namespace ChuckBartowski\PleskSdk\Api;

use ChuckBartowski\PleskSdk\Response\ApiResponse;

final class CustomerApi extends AbstractApi
{
    public function list(): ApiResponse
    {
        return $this->get('/clients');
    }

    public function find(int $id): ApiResponse
    {
        return $this->get('/clients/'.$id);
    }

    public function create(string $name, string $login, string $password, string $email, array $options = []): ApiResponse
    {
        return $this->post('/clients', array_merge($options, [
            'name' => $name,
            'login' => $login,
            'password' => $password,
            'email' => $email,
            'type' => $options['type'] ?? 'customer',
        ]));
    }

    public function update(int $id, array $properties): ApiResponse
    {
        return $this->put('/clients/'.$id, $properties);
    }

    public function remove(int $id): ApiResponse
    {
        return $this->delete('/clients/'.$id);
    }

    public function domains(int $id): ApiResponse
    {
        return $this->get(sprintf('/clients/%d/domains', $id));
    }

    public function statistics(int $id): ApiResponse
    {
        return $this->get(sprintf('/clients/%d/statistics', $id));
    }

    public function suspend(string $login): ApiResponse
    {
        return $this->cli('customer', ['--off', $login]);
    }

    public function activate(string $login): ApiResponse
    {
        return $this->cli('customer', ['--on', $login]);
    }
}
