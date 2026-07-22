<?php

declare(strict_types=1);

use ChuckBartowski\PleskSdk\Client\PleskClient;
use ChuckBartowski\PleskSdk\Exception\PleskSdkExceptionInterface;
use ChuckBartowski\PleskSdk\Plesk;

if (!defined('WHMCS')) {
    die('This file cannot be accessed directly');
}

function plesksdk_MetaData(): array
{
    return [
        'DisplayName' => 'Plesk (SDK)',
        'APIVersion' => '1.1',
        'RequiresServer' => true,
        'DefaultNonSSLPort' => '8880',
        'DefaultSSLPort' => '8443',
    ];
}

function plesksdk_ConfigOptions(): array
{
    return [
        'Service Plan' => [
            'Type' => 'text',
            'Size' => '25',
            'Description' => 'Plesk service plan name',
        ],
    ];
}

function plesksdk_plesk(array $params): Plesk
{
    $client = new PleskClient(
        host: $params['serverhostname'] ?: $params['serverip'],
        apiKey: $params['serveraccesshash'] ?: '',
        login: $params['serverusername'] ?: 'admin',
        password: $params['serverpassword'] ?: '',
        port: (int) ($params['serverport'] ?: 8443),
        verifySsl: (bool) $params['serversecure'],
    );

    return new Plesk($client);
}

function plesksdk_TestConnection(array $params): array
{
    try {
        plesksdk_plesk($params)->server()->info();

        return ['success' => true, 'error' => ''];
    } catch (PleskSdkExceptionInterface $e) {
        return ['success' => false, 'error' => $e->getMessage()];
    }
}

function plesksdk_CreateAccount(array $params): string
{
    try {
        plesksdk_plesk($params)->domains()->create($params['domain'], array_filter([
            'hosting_type' => 'virtual',
            'hosting_settings' => [
                'ftp_login' => $params['username'],
                'ftp_password' => $params['password'],
            ],
            'plan' => $params['configoption1'] ? ['name' => $params['configoption1']] : null,
        ]));

        return 'success';
    } catch (PleskSdkExceptionInterface $e) {
        return $e->getMessage();
    }
}

function plesksdk_SuspendAccount(array $params): string
{
    try {
        plesksdk_plesk($params)->domains()->suspend($params['domain']);

        return 'success';
    } catch (PleskSdkExceptionInterface $e) {
        return $e->getMessage();
    }
}

function plesksdk_UnsuspendAccount(array $params): string
{
    try {
        plesksdk_plesk($params)->domains()->activate($params['domain']);

        return 'success';
    } catch (PleskSdkExceptionInterface $e) {
        return $e->getMessage();
    }
}

function plesksdk_TerminateAccount(array $params): string
{
    try {
        plesksdk_plesk($params)->cli()->call('domain', ['--remove', $params['domain']]);

        return 'success';
    } catch (PleskSdkExceptionInterface $e) {
        return $e->getMessage();
    }
}

function plesksdk_ChangePassword(array $params): string
{
    try {
        plesksdk_plesk($params)->cli()->call('site', ['--update-web-user', $params['domain'], '-passwd', $params['password']]);

        return 'success';
    } catch (PleskSdkExceptionInterface $e) {
        return $e->getMessage();
    }
}
