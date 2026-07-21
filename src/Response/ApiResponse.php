<?php

declare(strict_types=1);

namespace ChuckBartowski\PleskSdk\Response;

use ChuckBartowski\PleskSdk\Exception\ApiException;

final readonly class ApiResponse
{
    public function __construct(
        public bool $success,
        public int $statusCode,
        public mixed $data,
        public array $errors,
        public mixed $raw,
    ) {
    }

    public static function fromHttp(int $statusCode, mixed $payload): self
    {
        $success = $statusCode >= 200 && $statusCode < 300;
        $errors = [];

        if (!$success) {
            $message = \is_array($payload) ? ($payload['message'] ?? null) : null;
            $errors = [null !== $message ? (string) $message : sprintf('HTTP %d', $statusCode)];
        }

        return new self($success, $statusCode, $payload, $errors, $payload);
    }

    public function withFailure(array $errors): self
    {
        return new self(false, $this->statusCode, $this->data, $errors, $this->raw);
    }

    public function ensureSuccess(): self
    {
        if (!$this->success) {
            throw new ApiException($this->errors ?: ['Plesk API call failed'], $this->statusCode, $this->raw);
        }

        return $this;
    }

    public function data(?string $key = null, mixed $default = null): mixed
    {
        if (null === $key) {
            return $this->data;
        }

        return \is_array($this->data) ? ($this->data[$key] ?? $default) : $default;
    }

    public function stdout(): string
    {
        return trim((string) $this->data('stdout', ''));
    }

    public function stderr(): string
    {
        return trim((string) $this->data('stderr', ''));
    }
}
