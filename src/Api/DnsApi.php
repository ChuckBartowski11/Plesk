<?php

declare(strict_types=1);

namespace ChuckBartowski\PleskSdk\Api;

use ChuckBartowski\PleskSdk\Response\ApiResponse;

final class DnsApi extends AbstractApi
{
    public function records(?string $domain = null): ApiResponse
    {
        return $this->get('/dns/records', array_filter(['domain' => $domain]));
    }

    public function addRecord(string $domain, string $type, string $host, string $value, ?int $ttl = null, ?string $opt = null): ApiResponse
    {
        return $this->post('/dns/records', array_filter([
            'domain' => $domain,
            'type' => $type,
            'host' => $host,
            'value' => $value,
            'ttl' => $ttl,
            'opt' => $opt,
        ], static fn (mixed $v): bool => null !== $v));
    }

    public function deleteRecord(int $id): ApiResponse
    {
        return $this->delete('/dns/records/'.$id);
    }

    public function enableZone(string $domain): ApiResponse
    {
        return $this->cli('dns', ['--on', $domain]);
    }

    public function disableZone(string $domain): ApiResponse
    {
        return $this->cli('dns', ['--off', $domain]);
    }
}
