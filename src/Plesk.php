<?php

declare(strict_types=1);

namespace ChuckBartowski\PleskSdk;

use ChuckBartowski\PleskSdk\Api\AuthApi;
use ChuckBartowski\PleskSdk\Api\CliApi;
use ChuckBartowski\PleskSdk\Api\CustomerApi;
use ChuckBartowski\PleskSdk\Api\DatabaseApi;
use ChuckBartowski\PleskSdk\Api\DnsApi;
use ChuckBartowski\PleskSdk\Api\DomainApi;
use ChuckBartowski\PleskSdk\Api\ExtensionApi;
use ChuckBartowski\PleskSdk\Api\FtpApi;
use ChuckBartowski\PleskSdk\Api\MailApi;
use ChuckBartowski\PleskSdk\Api\ServerApi;
use ChuckBartowski\PleskSdk\Client\PleskClient;

final class Plesk
{
    private array $apis = [];

    public function __construct(private readonly PleskClient $client)
    {
    }

    public function client(): PleskClient
    {
        return $this->client;
    }

    public function server(): ServerApi
    {
        return $this->apis[ServerApi::class] ??= new ServerApi($this->client);
    }

    public function customers(): CustomerApi
    {
        return $this->apis[CustomerApi::class] ??= new CustomerApi($this->client);
    }

    public function domains(): DomainApi
    {
        return $this->apis[DomainApi::class] ??= new DomainApi($this->client);
    }

    public function dns(): DnsApi
    {
        return $this->apis[DnsApi::class] ??= new DnsApi($this->client);
    }

    public function databases(): DatabaseApi
    {
        return $this->apis[DatabaseApi::class] ??= new DatabaseApi($this->client);
    }

    public function ftp(): FtpApi
    {
        return $this->apis[FtpApi::class] ??= new FtpApi($this->client);
    }

    public function mail(): MailApi
    {
        return $this->apis[MailApi::class] ??= new MailApi($this->client);
    }

    public function extensions(): ExtensionApi
    {
        return $this->apis[ExtensionApi::class] ??= new ExtensionApi($this->client);
    }

    public function auth(): AuthApi
    {
        return $this->apis[AuthApi::class] ??= new AuthApi($this->client);
    }

    public function cli(): CliApi
    {
        return $this->apis[CliApi::class] ??= new CliApi($this->client);
    }
}
