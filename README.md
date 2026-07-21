# Plesk SDK for PHP

A modern, fully typed PHP SDK for the **Plesk REST API (v2)**, with a built-in **CLI bridge** for everything the REST surface does not cover (mail accounts, suspensions, service management…).

Framework-agnostic core — usable from any PHP project, script, or worker — with an optional bundle for first-class Symfony integration. Authenticated with API keys or basic credentials, typed exceptions, and a comment-free, strictly typed codebase (PHP 8.2+, `declare(strict_types=1)` everywhere).

---

## Table of Contents

- [Features](#features)
- [Requirements](#requirements)
- [Installation](#installation)
- [Quick Start (plain PHP)](#quick-start-plain-php)
- [Symfony Integration (optional)](#symfony-integration-optional)
- [Architecture](#architecture)
- [Authentication](#authentication)
- [API Reference](#api-reference)
  - [Server](#server)
  - [Customers](#customers)
  - [Domains](#domains)
  - [DNS](#dns)
  - [Databases](#databases)
  - [FTP](#ftp)
  - [Mail (CLI bridge)](#mail-cli-bridge)
  - [Extensions](#extensions)
  - [API keys](#api-keys)
  - [CLI (generic bridge)](#cli-generic-bridge)
- [Responses](#responses)
- [Error Handling](#error-handling)
- [Testing](#testing)
- [Security Notes](#security-notes)
- [License](#license)

---

## Features

- **Full REST API v2 coverage**: server, clients, domains, DNS, databases, FTP users, extensions, API keys.
- **CLI bridge** (`POST /cli/{command}/call`): every `plesk bin` utility becomes callable over HTTPS — the SDK uses it transparently where REST has gaps (mailboxes, suspend/activate, services) and exposes it directly for everything else.
- **Framework-agnostic**: one plain facade (`Plesk`) you can instantiate anywhere; the only hard dependency is `symfony/http-client`, a standalone component that works in any PHP project.
- **Two auth modes**: `X-API-Key` header (recommended) or HTTP basic auth — plus a helper to mint API keys from credentials.
- **A single normalized response object** (`ApiResponse`) with status code, data accessor, error list, and `stdout()`/`stderr()` helpers for CLI calls.
- **Typed exception hierarchy** under one marker interface, so you can catch narrowly or broadly.
- **Optional Symfony bundle** with semantic configuration and autowirable services.
- **Fully unit-tested** against `MockHttpClient` (no network required).

## Requirements

| Dependency | Version |
|---|---|
| PHP | >= 8.2 |
| Plesk | Obsidian 18.x (REST API v2 enabled by default) |
| Symfony | 6.4 LTS or 7.x — **optional**, only for the bundle integration |

## Installation

The package is not published on Packagist yet — install it straight from the Git repository. Add the repository to your project's `composer.json`:

```json
{
    "repositories": [
        {
            "type": "vcs",
            "url": "https://github.com/ChuckBartowski11/Plesk"
        }
    ]
}
```

Then require the development branch:

```bash
composer require chuckbartowski/plesk-sdk:dev-main
```

## Quick Start (plain PHP)

No framework required — build the client and go:

```php
use ChuckBartowski\PleskSdk\Client\PleskClient;
use ChuckBartowski\PleskSdk\Plesk;

$plesk = new Plesk(new PleskClient(
    host: 'plesk.example.com',
    apiKey: getenv('PLESK_API_KEY'),
));

$plesk->domains()->create('example.com', [
    'hosting_type' => 'virtual',
    'hosting_settings' => ['ftp_login' => 'deploy', 'ftp_password' => 'S3cret!'],
]);

$plesk->mail()->create('contact@example.com', 'S3cure!Pass');
$plesk->dns()->addRecord('example.com', 'A', 'www', '203.0.113.10');
```

Client constructor signature:

```php
new PleskClient(
    string $host,
    string $apiKey = '',                      // preferred
    string $login = '',                       // fallback: basic auth pair
    string $password = '',
    int $port = 8443,
    bool $verifySsl = true,
    float $timeout = 30.0,
    ?HttpClientInterface $httpClient = null,  // inject your own (retries, proxy, mock…)
);
```

## Symfony Integration (optional)

Register the bundle:

```php
// config/bundles.php
return [
    ChuckBartowski\PleskSdk\PleskSdkBundle::class => ['all' => true],
];
```

Then create `config/packages/plesk_sdk.yaml`:

```yaml
plesk_sdk:
    host: '%env(PLESK_HOST)%'
    api_key: '%env(PLESK_API_KEY)%'
    port: 8443
    verify_ssl: true
    timeout: 30
```

```dotenv
# .env.local
PLESK_HOST=plesk.example.com
PLESK_API_KEY=XXXXXXXX-XXXX-XXXX-XXXX-XXXXXXXXXXXX
```

### Configuration reference

| Key | Type | Default | Description |
|---|---|---|---|
| `host` | string | *required* | Plesk server hostname (no scheme, no port) |
| `api_key` | string | `''` | API key sent as `X-API-Key` (preferred) |
| `login` / `password` | string | `''` | Basic auth pair, used when `api_key` is empty |
| `port` | int | `8443` | Plesk panel TLS port |
| `verify_ssl` | bool | `true` | TLS peer/host verification |
| `timeout` | float | `30.0` | Per-request timeout in seconds |

The `Plesk` facade is then autowirable in controllers, services, commands, and message handlers. The bundle reuses your application's `http_client` service when available and falls back to a native client otherwise.

## Architecture

```
src/
├── PleskSdkBundle.php           Symfony bundle: config tree + service wiring (optional)
├── Plesk.php                    Facade: entry point for all modules
├── Client/
│   └── PleskClient.php          REST transport, auth, JSON handling, CLI bridge
├── Response/
│   └── ApiResponse.php          Immutable normalized response (+ stdout/stderr helpers)
├── Exception/
│   ├── PleskSdkExceptionInterface.php
│   ├── ApiException.php         API answered but reported a failure
│   ├── AuthenticationException.php
│   └── TransportException.php   Network / TLS / timeout / invalid JSON
└── Api/
    ├── AbstractApi.php
    ├── ServerApi.php            CustomerApi.php   DomainApi.php   DnsApi.php
    ├── DatabaseApi.php          FtpApi.php        MailApi.php     ExtensionApi.php
    └── AuthApi.php              CliApi.php
```

Design decisions:

- **Facade + lazy modules**: `Plesk` instantiates each module on first use and caches it.
- **Modules always validate**: every module method calls `ensureSuccess()` internally and throws `ApiException` on failure. To inspect a failed response without an exception, drop down to the client level.
- **CLI exit codes are failures**: a CLI call returning a non-zero `code` produces a failed response with `stderr` as the error, even though HTTP said 200.
- **Nothing is sealed off**: the client's `get`/`post`/`put`/`delete`/`cli` methods accept any path, so a REST endpoint added tomorrow is usable today.

## Authentication

Two modes, checked in order:

1. **API key** (recommended) — sent as `X-API-Key`. Create one from credentials once, then store only the key:

```php
$bootstrap = new Plesk(new PleskClient('plesk.example.com', login: 'admin', password: $password));
$key = $bootstrap->auth()->createKey(description: 'provisioning worker')->data('key');
```

2. **Basic auth** — `login`/`password`, used when no API key is configured.

Missing credentials throw an `AuthenticationException` immediately, before any network request.

## API Reference

Every method returns an [`ApiResponse`](#responses) and throws on failure (see [Error Handling](#error-handling)). Methods marked **CLI** go through the CLI bridge rather than a REST endpoint.

### Server

`$plesk->server()`

| Method | Endpoint |
|---|---|
| `info()` | `GET /server` |
| `ips()` | `GET /server/ips` |
| `addIp(string $ip, string $netmask, string $interface, string $type = 'shared')` | `POST /server/ips` |
| `deleteIp(string $ip)` | `DELETE /server/ips/{ip}` |
| `serviceStatus()` | **CLI** `service --status` |
| `restartService(string $service)` | **CLI** `service --restart` |

### Customers

`$plesk->customers()` — client accounts (the people who own subscriptions).

| Method | Endpoint |
|---|---|
| `list()` / `find(int $id)` | `GET /clients`, `GET /clients/{id}` |
| `create(string $name, string $login, string $password, string $email, array $options = [])` | `POST /clients` |
| `update(int $id, array $properties)` | `PUT /clients/{id}` |
| `remove(int $id)` | `DELETE /clients/{id}` |
| `domains(int $id)` | `GET /clients/{id}/domains` |
| `statistics(int $id)` | `GET /clients/{id}/statistics` |
| `suspend(string $login)` / `activate(string $login)` | **CLI** `customer --off/--on` |

```php
$plesk->customers()->create('ACME Corp', 'acme', 'S3cret!', 'billing@acme.test');
$plesk->customers()->suspend('acme');
```

### Domains

`$plesk->domains()`

| Method | Endpoint |
|---|---|
| `list(array $filters = [])` / `find(int $id)` | `GET /domains`, `GET /domains/{id}` |
| `create(string $name, array $options = [])` | `POST /domains` |
| `update(int $id, array $properties)` | `PUT /domains/{id}` |
| `remove(int $id)` | `DELETE /domains/{id}` |
| `owner(int $id)` | `GET /domains/{id}/client` |
| `status(int $id)` | `GET /domains/{id}/status` |
| `suspend(string $name)` / `activate(string $name)` | **CLI** `domain --off/--on` |

`create()` options follow the REST schema: `hosting_type` (`virtual`, `standard_forwarding`, `frame_forwarding`, `none`), `hosting_settings`, `owner_client` (`{id}` or `{login}`), `plan` (`{name}`), `ipv4`/`ipv6`.

```php
$plesk->domains()->create('customer1.com', [
    'hosting_type' => 'virtual',
    'hosting_settings' => ['ftp_login' => 'c1', 'ftp_password' => 'S3cret!'],
    'owner_client' => ['login' => 'acme'],
    'plan' => ['name' => 'Default Domain'],
]);
```

### DNS

`$plesk->dns()`

| Method | Endpoint |
|---|---|
| `records(?string $domain = null)` | `GET /dns/records` |
| `addRecord(string $domain, string $type, string $host, string $value, ?int $ttl = null, ?string $opt = null)` | `POST /dns/records` |
| `deleteRecord(int $id)` | `DELETE /dns/records/{id}` |
| `enableZone(string $domain)` / `disableZone(string $domain)` | **CLI** `dns --on/--off` |

`opt` carries the type-specific extra (MX priority, SRV weight…). Records are addressed by numeric id — fetch them first.

### Databases

`$plesk->databases()`

| Method | Endpoint |
|---|---|
| `list(?string $domain = null)` | `GET /databases` |
| `create(string $name, string $domain, string $type = 'mysql', ?int $serverId = null)` | `POST /databases` |
| `remove(int $id)` | `DELETE /databases/{id}` |
| `users(?int $databaseId = null)` | `GET /dbusers` |
| `createUser(string $login, string $password, int $databaseId)` | `POST /dbusers` |
| `removeUser(int $id)` | `DELETE /dbusers/{id}` |

### FTP

`$plesk->ftp()` — additional FTP users: `list()`, `create(name, password, domain, ?home)`, `update(name, properties)`, `remove(name)` over `GET|POST|PUT|DELETE /ftpusers`.

### Mail (CLI bridge)

`$plesk->mail()` — REST v2 has no mail endpoints, so this module drives the `mail` CLI utility.

| Method | CLI equivalent |
|---|---|
| `create(string $address, string $password, bool $mailbox = true)` | `mail --create -passwd -mailbox` |
| `remove(string $address)` | `mail --remove` |
| `info(string $address)` | `mail --info` |
| `changePassword(string $address, string $password)` | `mail --update -passwd` |
| `setMailboxQuota(string $address, string $quota)` | `mail --update -mbox_quota` (e.g. `'1G'`, `'500M'`) |
| `addAlias(...)` / `removeAlias(...)` | `mail --update -aliases add:/del:` |
| `enableForwarding(string $address, string $destination)` / `disableForwarding(...)` | `mail --update -forwarding` |

```php
$plesk->mail()->create('support@example.com', 'S3cure!Pass');
$plesk->mail()->setMailboxQuota('support@example.com', '2G');
```

### Extensions

`$plesk->extensions()` — `list()`, `install(idOrUrl)`, `uninstall(id)` over `/extensions`; `enable(id)` / `disable(id)` via **CLI** `extension`.

### API keys

`$plesk->auth()` — `createKey(?ip, ?ttl, ?description, ?login)` (`POST /auth/keys`, requires basic auth) and `deleteKey(key)`.

### CLI (generic bridge)

`$plesk->cli()` — the raw escape hatch to every `plesk bin` utility.

| Method | Endpoint |
|---|---|
| `commands()` | `GET /cli/commands` |
| `reference(string $command)` | `GET /cli/{command}/ref` |
| `call(string $command, array $params = [], array $env = [])` | `POST /cli/{command}/call` |

```php
$plesk->cli()->call('subscription', ['--info', 'customer1.com']);
$plesk->cli()->call('php_handler', ['--list']);
```

## Responses

All calls return an immutable `ApiResponse`:

```php
$response = $plesk->domains()->list();

$response->success;          // bool — 2xx and, for CLI calls, exit code 0
$response->statusCode;       // int
$response->data;             // mixed — decoded JSON body (null on 204)
$response->data('id');       // keyed access with optional default
$response->errors;           // list<string>
$response->raw;              // complete decoded payload
$response->stdout();         // CLI calls: trimmed standard output
$response->stderr();         // CLI calls: trimmed standard error
```

## Error Handling

All SDK exceptions implement `PleskSdkExceptionInterface`, so a single catch covers everything:

| Exception | Thrown when | Extras |
|---|---|---|
| `ApiException` | The API answered but reported a failure (module methods validate automatically) | `getErrors()`, `getStatusCode()`, `getRaw()` |
| `AuthenticationException` | Credentials are missing, or the server answered HTTP 401/403 | thrown *before* any request when credentials are empty |
| `TransportException` | Network error, TLS failure, timeout, or a non-JSON response body | wraps the underlying `symfony/http-client` exception |

```php
use ChuckBartowski\PleskSdk\Exception\ApiException;
use ChuckBartowski\PleskSdk\Exception\PleskSdkExceptionInterface;

try {
    $plesk->domains()->create('example.com', ['hosting_type' => 'virtual']);
} catch (ApiException $e) {
    $this->logger->warning('Plesk rejected the domain', ['errors' => $e->getErrors()]);
} catch (PleskSdkExceptionInterface $e) {
    throw new ProvisioningUnavailableException(previous: $e);
}
```

To inspect a failed response without exceptions, use the client directly — client-level methods return the response as-is:

```php
$response = $plesk->client()->post('/domains', $payload);
if (!$response->success) {
    // $response->statusCode, $response->errors, $response->raw
}
```

## Testing

The suite runs entirely offline against `MockHttpClient`:

```bash
composer install
vendor/bin/phpunit
```

To test your own services, inject a `PleskClient` built with a mock:

```php
use ChuckBartowski\PleskSdk\Client\PleskClient;
use ChuckBartowski\PleskSdk\Plesk;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\JsonMockResponse;

$http = new MockHttpClient(new JsonMockResponse(['id' => 1]));
$plesk = new Plesk(new PleskClient('host', apiKey: 'key', httpClient: $http));
```

## Security Notes

- Secrets (`apiKey`, `password`) are passed with `#[\SensitiveParameter]`, so they never appear in stack traces.
- Prefer API keys over basic auth: they can be scoped to an IP (`createKey(ip: ...)`), given a TTL, and revoked individually.
- Keep credentials in `.env.local` or your secret vault — never commit them.
- The CLI bridge runs commands **as root on the server** — treat any code path that reaches `cli()` with the same care as SSH access.
- Leave `verify_ssl: true` in production; the option exists solely for self-signed panels.
- `remove()` on domains, customers and databases is irreversible — gate destructive calls behind confirmation flows in your application.

## License

MIT
