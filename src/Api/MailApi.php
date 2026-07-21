<?php

declare(strict_types=1);

namespace ChuckBartowski\PleskSdk\Api;

use ChuckBartowski\PleskSdk\Response\ApiResponse;

final class MailApi extends AbstractApi
{
    public function create(string $address, string $password, bool $mailbox = true): ApiResponse
    {
        return $this->cli('mail', [
            '--create', $address,
            '-passwd', $password,
            '-mailbox', $mailbox ? 'true' : 'false',
        ]);
    }

    public function remove(string $address): ApiResponse
    {
        return $this->cli('mail', ['--remove', $address]);
    }

    public function info(string $address): ApiResponse
    {
        return $this->cli('mail', ['--info', $address]);
    }

    public function changePassword(string $address, string $password): ApiResponse
    {
        return $this->cli('mail', ['--update', $address, '-passwd', $password]);
    }

    public function setMailboxQuota(string $address, string $quota): ApiResponse
    {
        return $this->cli('mail', ['--update', $address, '-mbox_quota', $quota]);
    }

    public function addAlias(string $address, string $alias): ApiResponse
    {
        return $this->cli('mail', ['--update', $address, '-aliases', 'add:'.$alias]);
    }

    public function removeAlias(string $address, string $alias): ApiResponse
    {
        return $this->cli('mail', ['--update', $address, '-aliases', 'del:'.$alias]);
    }

    public function enableForwarding(string $address, string $destination): ApiResponse
    {
        return $this->cli('mail', ['--update', $address, '-forwarding', 'true', '-forwarding-addresses', 'add:'.$destination]);
    }

    public function disableForwarding(string $address): ApiResponse
    {
        return $this->cli('mail', ['--update', $address, '-forwarding', 'false']);
    }
}
