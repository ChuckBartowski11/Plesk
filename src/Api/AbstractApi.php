<?php

declare(strict_types=1);

namespace ChuckBartowski\PleskSdk\Api;

use ChuckBartowski\PleskSdk\Client\PleskClient;
use ChuckBartowski\PleskSdk\Response\ApiResponse;

abstract class AbstractApi
{
    public function __construct(protected readonly PleskClient $client)
    {
    }

    protected function get(string $path, array $query = []): ApiResponse
    {
        return $this->client->get($path, $query)->ensureSuccess();
    }

    protected function post(string $path, array $json = []): ApiResponse
    {
        return $this->client->post($path, $json)->ensureSuccess();
    }

    protected function put(string $path, array $json = []): ApiResponse
    {
        return $this->client->put($path, $json)->ensureSuccess();
    }

    protected function delete(string $path): ApiResponse
    {
        return $this->client->delete($path)->ensureSuccess();
    }

    protected function cli(string $command, array $params = [], array $env = []): ApiResponse
    {
        return $this->client->cli($command, $params, $env)->ensureSuccess();
    }
}
