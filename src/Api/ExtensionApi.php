<?php

declare(strict_types=1);

namespace ChuckBartowski\PleskSdk\Api;

use ChuckBartowski\PleskSdk\Response\ApiResponse;

final class ExtensionApi extends AbstractApi
{
    public function list(): ApiResponse
    {
        return $this->get('/extensions');
    }

    public function install(string $idOrUrl): ApiResponse
    {
        $key = str_starts_with($idOrUrl, 'http') ? 'url' : 'id';

        return $this->post('/extensions', [$key => $idOrUrl]);
    }

    public function uninstall(string $id): ApiResponse
    {
        return $this->delete('/extensions/'.rawurlencode($id));
    }

    public function enable(string $id): ApiResponse
    {
        return $this->cli('extension', ['--enable', $id]);
    }

    public function disable(string $id): ApiResponse
    {
        return $this->cli('extension', ['--disable', $id]);
    }
}
