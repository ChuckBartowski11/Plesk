<?php

declare(strict_types=1);

namespace ChuckBartowski\PleskSdk\Api;

use ChuckBartowski\PleskSdk\Response\ApiResponse;

final class ServerApi extends AbstractApi
{
    public function info(): ApiResponse
    {
        return $this->get('/server');
    }

    public function ips(): ApiResponse
    {
        return $this->get('/server/ips');
    }

    public function addIp(string $ip, string $netmask, string $interface, string $type = 'shared'): ApiResponse
    {
        return $this->post('/server/ips', [
            'ip' => $ip,
            'netmask' => $netmask,
            'interface' => $interface,
            'type' => $type,
        ]);
    }

    public function deleteIp(string $ip): ApiResponse
    {
        return $this->delete('/server/ips/'.rawurlencode($ip));
    }

    public function serviceStatus(): ApiResponse
    {
        return $this->cli('service', ['--status']);
    }

    public function restartService(string $service): ApiResponse
    {
        return $this->cli('service', ['--restart', $service]);
    }
}
