<?php

declare(strict_types=1);

namespace ChuckBartowski\PleskSdk\Api;

use ChuckBartowski\PleskSdk\Response\ApiResponse;

final class CliApi extends AbstractApi
{
    public function commands(): ApiResponse
    {
        return $this->get('/cli/commands');
    }

    public function reference(string $command): ApiResponse
    {
        return $this->get(sprintf('/cli/%s/ref', $command));
    }

    public function call(string $command, array $params = [], array $env = []): ApiResponse
    {
        return $this->cli($command, $params, $env);
    }
}
