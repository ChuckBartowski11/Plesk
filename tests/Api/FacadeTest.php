<?php

declare(strict_types=1);

namespace ChuckBartowski\PleskSdk\Tests\Api;

use ChuckBartowski\PleskSdk\Client\PleskClient;
use ChuckBartowski\PleskSdk\Plesk;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\JsonMockResponse;

final class FacadeTest extends TestCase
{
    private function plesk(MockHttpClient $http): Plesk
    {
        return new Plesk(new PleskClient('plesk.example.com', apiKey: 'K3Y', httpClient: $http));
    }

    public function testDomainCreatePostsToDomains(): void
    {
        $http = new MockHttpClient(function (string $method, string $url, array $options): JsonMockResponse {
            $this->assertSame('POST', $method);
            $this->assertStringEndsWith('/api/v2/domains', $url);
            $body = json_decode($options['body'], true);
            $this->assertSame('example.com', $body['name']);
            $this->assertSame('virtual', $body['hosting_type']);

            return new JsonMockResponse(['id' => 7], ['http_code' => 201]);
        });

        $response = $this->plesk($http)->domains()->create('example.com', [
            'hosting_type' => 'virtual',
            'hosting_settings' => ['ftp_login' => 'u1', 'ftp_password' => 'p1'],
        ]);

        $this->assertSame(7, $response->data('id'));
    }

    public function testDnsAddRecordFiltersNullValues(): void
    {
        $http = new MockHttpClient(function (string $method, string $url, array $options): JsonMockResponse {
            $this->assertStringEndsWith('/api/v2/dns/records', $url);
            $body = json_decode($options['body'], true);
            $this->assertSame(['domain', 'type', 'host', 'value'], array_keys($body));
            $this->assertSame('A', $body['type']);

            return new JsonMockResponse(['id' => 55], ['http_code' => 201]);
        });

        $this->plesk($http)->dns()->addRecord('example.com', 'A', 'www', '203.0.113.10');
    }

    public function testMailCreateGoesThroughCliBridge(): void
    {
        $http = new MockHttpClient(function (string $method, string $url, array $options): JsonMockResponse {
            $this->assertStringEndsWith('/api/v2/cli/mail/call', $url);
            $this->assertJsonStringEqualsJsonString(
                '{"params":["--create","box@example.com","-passwd","S3cret!","-mailbox","true"]}',
                $options['body'],
            );

            return new JsonMockResponse(['code' => 0, 'stdout' => 'SUCCESS', 'stderr' => '']);
        });

        $response = $this->plesk($http)->mail()->create('box@example.com', 'S3cret!');

        $this->assertTrue($response->success);
    }

    public function testCustomerSuspendUsesCliBridge(): void
    {
        $http = new MockHttpClient(function (string $method, string $url, array $options): JsonMockResponse {
            $this->assertStringEndsWith('/api/v2/cli/customer/call', $url);
            $this->assertJsonStringEqualsJsonString('{"params":["--off","customer1"]}', $options['body']);

            return new JsonMockResponse(['code' => 0, 'stdout' => '', 'stderr' => '']);
        });

        $this->plesk($http)->customers()->suspend('customer1');
    }

    public function testDatabaseCreateNestsParentDomain(): void
    {
        $http = new MockHttpClient(function (string $method, string $url, array $options): JsonMockResponse {
            $this->assertStringEndsWith('/api/v2/databases', $url);
            $body = json_decode($options['body'], true);
            $this->assertSame(['name' => 'example.com'], $body['parent_domain']);
            $this->assertSame('mysql', $body['type']);

            return new JsonMockResponse(['id' => 3], ['http_code' => 201]);
        });

        $this->plesk($http)->databases()->create('app_db', 'example.com');
    }

    public function testFacadeCachesApiInstances(): void
    {
        $plesk = $this->plesk(new MockHttpClient());

        $this->assertSame($plesk->domains(), $plesk->domains());
        $this->assertSame($plesk->mail(), $plesk->mail());
        $this->assertSame($plesk->cli(), $plesk->cli());
    }
}
