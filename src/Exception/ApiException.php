<?php

declare(strict_types=1);

namespace ChuckBartowski\PleskSdk\Exception;

final class ApiException extends \RuntimeException implements PleskSdkExceptionInterface
{
    public function __construct(
        private readonly array $errors,
        private readonly int $statusCode = 0,
        private readonly mixed $raw = null,
    ) {
        parent::__construct(implode('; ', array_map('strval', $errors)) ?: 'Plesk API call failed');
    }

    public function getErrors(): array
    {
        return $this->errors;
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    public function getRaw(): mixed
    {
        return $this->raw;
    }
}
