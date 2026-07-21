<?php

declare(strict_types=1);

namespace ChuckBartowski\PleskSdk\Client;

use ChuckBartowski\PleskSdk\Exception\AuthenticationException;
use ChuckBartowski\PleskSdk\Exception\TransportException;
use ChuckBartowski\PleskSdk\Response\ApiResponse;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

final class PleskClient
{
    private readonly HttpClientInterface $httpClient;

    public function __construct(
        private readonly string $host,
        #[\SensitiveParameter]
        private readonly string $apiKey = '',
        private readonly string $login = '',
        #[\SensitiveParameter]
        private readonly string $password = '',
        private readonly int $port = 8443,
        private readonly bool $verifySsl = true,
        private readonly float $timeout = 30.0,
        ?HttpClientInterface $httpClient = null,
    ) {
        $this->httpClient = $httpClient ?? HttpClient::create();
    }

    public function getHost(): string
    {
        return $this->host;
    }

    public function get(string $path, array $query = []): ApiResponse
    {
        return $this->request('GET', $path, $query);
    }

    public function post(string $path, array $json = [], array $query = []): ApiResponse
    {
        return $this->request('POST', $path, $query, $json);
    }

    public function put(string $path, array $json = [], array $query = []): ApiResponse
    {
        return $this->request('PUT', $path, $query, $json);
    }

    public function delete(string $path, array $query = []): ApiResponse
    {
        return $this->request('DELETE', $path, $query);
    }

    public function cli(string $command, array $params = [], array $env = []): ApiResponse
    {
        $body = ['params' => array_map('strval', array_values($params))];

        if ([] !== $env) {
            $body['env'] = $env;
        }

        $response = $this->request('POST', sprintf('/cli/%s/call', $command), [], $body);

        if ($response->success && 0 !== (int) $response->data('code', 0)) {
            return $response->withFailure(
                '' !== $response->stderr() ? [$response->stderr()] : [sprintf("CLI command '%s' failed", $command)],
            );
        }

        return $response;
    }

    public function request(string $method, string $path, array $query = [], ?array $json = null): ApiResponse
    {
        $options = [
            'headers' => $this->authHeaders(),
            'query' => $query,
            'timeout' => $this->timeout,
            'verify_peer' => $this->verifySsl,
            'verify_host' => $this->verifySsl,
        ];

        if (null !== $json) {
            $options['json'] = $json;
        }

        $url = sprintf('https://%s:%d/api/v2%s', $this->host, $this->port, $path);

        try {
            $response = $this->httpClient->request($method, $url, $options);
            $statusCode = $response->getStatusCode();

            if (401 === $statusCode || 403 === $statusCode) {
                throw new AuthenticationException(sprintf('Authentication failed on %s (HTTP %d)', $this->host, $statusCode));
            }

            $content = $response->getContent(false);
        } catch (TransportExceptionInterface $e) {
            throw new TransportException($e->getMessage(), 0, $e);
        }

        $payload = null;

        if ('' !== $content) {
            try {
                $payload = json_decode($content, true, 512, \JSON_THROW_ON_ERROR);
            } catch (\JsonException $e) {
                throw new TransportException(sprintf('Invalid JSON response from %s', $this->host), 0, $e);
            }
        }

        return ApiResponse::fromHttp($statusCode, $payload);
    }

    private function authHeaders(): array
    {
        if ('' !== $this->apiKey) {
            return ['X-API-Key' => $this->apiKey];
        }

        if ('' !== $this->login && '' !== $this->password) {
            return ['Authorization' => 'Basic '.base64_encode($this->login.':'.$this->password)];
        }

        throw new AuthenticationException('Missing credentials: provide an API key or a login/password pair');
    }
}
