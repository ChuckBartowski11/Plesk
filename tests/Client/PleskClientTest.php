<?php

declare(strict_types=1);

namespace ChuckBartowski\PleskSdk\Tests\Client;

use ChuckBartowski\PleskSdk\Client\PleskClient;
use ChuckBartowski\PleskSdk\Exception\ApiException;
use ChuckBartowski\PleskSdk\Exception\AuthenticationException;
use ChuckBartowski\PleskSdk\Exception\TransportException;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\JsonMockResponse;
use Symfony\Component\HttpClient\Response\MockResponse;

final class PleskClientTest extends TestCase
{
    private function apiKeyClient(MockHttpClient $http): PleskClient
    {
        return new PleskClient('plesk.example.com', apiKey: 'K3Y', httpClient: $http);
    }

    public function testGetSendsApiKeyHeader(): void
    {
        $http = new MockHttpClient(function (string $method, string $url, array $options): JsonMockResponse {
            $this->assertSame('GET', $method);
            $this->assertSame('https://plesk.example.com:8443/api/v2/server', $url);
            $this->assertContains('X-API-Key: K3Y', $options['headers']);

            return new JsonMockResponse(['platform' => 'Linux', 'hostname' => 'plesk.example.com']);
        });

        $response = $this->apiKeyClient($http)->get('/server');

        $this->assertTrue($response->success);
        $this->assertSame(200, $response->statusCode);
        $this->assertSame('plesk.example.com', $response->data('hostname'));
    }

    public function testBasicAuthFallback(): void
    {
        $http = new MockHttpClient(function (string $method, string $url, array $options): JsonMockResponse {
            $expected = 'Authorization: Basic '.base64_encode('admin:s3cret');
            $this->assertContains($expected, $options['headers']);

            return new JsonMockResponse([]);
        });

        $client = new PleskClient('plesk.example.com', login: 'admin', password: 's3cret', httpClient: $http);

        $this->assertTrue($client->get('/server')->success);
    }

    public function testMissingCredentialsThrowsBeforeAnyRequest(): void
    {
        $client = new PleskClient('plesk.example.com', httpClient: new MockHttpClient());

        $this->expectException(AuthenticationException::class);
        $client->get('/server');
    }

    public function testUnauthorizedStatusThrowsAuthenticationException(): void
    {
        $http = new MockHttpClient(new JsonMockResponse(['code' => 1, 'message' => 'Invalid key'], ['http_code' => 401]));

        $this->expectException(AuthenticationException::class);
        $this->apiKeyClient($http)->get('/server');
    }

    public function testErrorStatusMapsMessageAndEnsureSuccessThrows(): void
    {
        $http = new MockHttpClient(new JsonMockResponse([
            'code' => 1007,
            'message' => 'Domain already exists.',
        ], ['http_code' => 400]));

        $response = $this->apiKeyClient($http)->post('/domains', ['name' => 'example.com']);

        $this->assertFalse($response->success);
        $this->assertSame(400, $response->statusCode);
        $this->assertSame(['Domain already exists.'], $response->errors);

        $this->expectException(ApiException::class);
        $this->expectExceptionMessage('Domain already exists.');
        $response->ensureSuccess();
    }

    public function testPostSendsJsonBody(): void
    {
        $http = new MockHttpClient(function (string $method, string $url, array $options): JsonMockResponse {
            $this->assertSame('POST', $method);
            $this->assertJsonStringEqualsJsonString(
                '{"name":"example.com","hosting_type":"virtual"}',
                $options['body'],
            );

            return new JsonMockResponse(['id' => 12], ['http_code' => 201]);
        });

        $response = $this->apiKeyClient($http)->post('/domains', ['name' => 'example.com', 'hosting_type' => 'virtual']);

        $this->assertTrue($response->success);
        $this->assertSame(12, $response->data('id'));
    }

    public function testEmptyBodyIsSuccessWithNullData(): void
    {
        $http = new MockHttpClient(new MockResponse('', ['http_code' => 204]));

        $response = $this->apiKeyClient($http)->delete('/domains/12');

        $this->assertTrue($response->success);
        $this->assertNull($response->data);
    }

    public function testInvalidJsonThrowsTransportException(): void
    {
        $http = new MockHttpClient(new MockResponse('<html>gateway error</html>', ['http_code' => 200]));

        $this->expectException(TransportException::class);
        $this->apiKeyClient($http)->get('/server');
    }

    public function testCliSuccess(): void
    {
        $http = new MockHttpClient(function (string $method, string $url, array $options): JsonMockResponse {
            $this->assertSame('https://plesk.example.com:8443/api/v2/cli/domain/call', $url);
            $this->assertJsonStringEqualsJsonString('{"params":["--info","example.com"]}', $options['body']);

            return new JsonMockResponse(['code' => 0, 'stdout' => 'Domain info', 'stderr' => '']);
        });

        $response = $this->apiKeyClient($http)->cli('domain', ['--info', 'example.com']);

        $this->assertTrue($response->success);
        $this->assertSame('Domain info', $response->stdout());
    }

    public function testCliNonZeroExitCodeIsFailure(): void
    {
        $http = new MockHttpClient(new JsonMockResponse([
            'code' => 1,
            'stdout' => '',
            'stderr' => 'An error occurred during domain creation.',
        ]));

        $response = $this->apiKeyClient($http)->cli('domain', ['--create', 'bad domain']);

        $this->assertFalse($response->success);
        $this->assertSame(['An error occurred during domain creation.'], $response->errors);
    }
}
