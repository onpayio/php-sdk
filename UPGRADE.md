# Upgrading from 1.x to 2.0

Every backwards-incompatible change in the `2.0` series is recorded here as it
lands. If you are upgrading, read this document top to bottom and check each
change against the code you use.

## Requirements

`2.0` requires **PHP 8.2 or later**. Support for PHP 7.4, 8.0, and 8.1 has been
dropped.

`2.0` also requires **`psr/log` `^2.0 || ^3.0`** — the SDK's default logger
implements PSR-3's `LoggerInterface`, so `psr/log` is a hard dependency. A project
pinning `psr/log` 1.x must upgrade it, even if it never uses the logging.

## Backwards-incompatible changes

### An installed PSR-18 HTTP client is now used by default

When no client is passed to the `OnPayAPI` constructor, the SDK auto-discovers an
installed PSR-18 client (via `php-http/discovery`) and uses it for all API and
OAuth traffic, falling back to the bundled cURL client only when none is
installed. If your project already has a PSR-18 client (e.g. Guzzle), OnPay
traffic now runs over its transport — its timeouts, proxies, retries, TLS
settings and middleware apply. The requests themselves are unchanged, and the
existing two-argument constructor, `getLastHttpRequest()` and
`getLastHttpResponse()` keep working.

### Public method signatures are now typed (Psalm level 1)

Most public methods gained native parameter and return types, and several
returns became nullable to reflect values they could always return:

- `OnPay\API\Http\Response::getStatusCode()` returns `?int` (was documented `string`);
  `Request`/`Response` getters (`getMethod`/`getUri`/`getBody`) return `?string`.
- `OnPayAPI::get()`/`post()` return `array`; `getPlatform()` returns `?string`;
  `getLastHttpRequest()`/`getLastHttpResponse()` are nullable.
- `TransactionCollection::$pagination` and `SubscriptionCollection::$pagination` are `?Pagination`.
- `StaticToken::getToken()` returns `?string`.
- Cart/PaymentWindow setters previously documented as `mixed` now document concrete types
  (e.g. `Cart::setShipping()`/`setHandling()` `$name` is `?string`).

Runtime behaviour for correct usage is unchanged. Only code that subclasses these
(non-`final`) classes is affected: overridden methods and redeclared (now typed)
properties must use a compatible signature.

### Stricter response and token validation

A few places that previously coerced malformed data now fail fast:

- A `200` response whose body is not a JSON object throws `ApiException`
  (previously it was silently treated as an empty result).
- A response object missing a field the API always returns — or returning it with
  the wrong type — throws `ApiException` when the SDK builds the value object
  (e.g. a transaction's `uuid`/`amount`/`created`), instead of yielding an object
  with `null` fields. Fields the API genuinely leaves out stay optional and are
  unaffected.
- `OnPay\OAuth\Client\AccessToken` throws `AccessTokenException` when a required
  field (`provider_id`, `issued_at`, `access_token`, `token_type`) is present but
  not a string.
- An OAuth callback with a missing or non-string `code`/`state` throws
  `OAuthException` immediately, instead of surfacing later as a state mismatch.

Well-formed API responses and tokens behave exactly as before.

### Interfaces you implement now require native types

If you implement one of the SDK's interfaces, your methods must declare matching
native types, or the class fatals at load:

- `OnPay\TokenStorageInterface` — `getToken(): ?string`
- `OnPay\OAuth\Client\TokenStorageInterface` — `getAccessTokenList(string $userId): array`,
  `storeAccessToken(string $userId, AccessToken $accessToken): void`,
  `deleteAccessToken(string $userId, AccessToken $accessToken): void`
- `OnPay\OAuth\Client\SessionInterface` — `take(string $key): mixed`, `set(string $key, mixed $value): void`
- `OnPay\OAuth\Client\Http\HttpClientInterface` — `send(Request $request): Response`; the
  `Response` constructor is `(int $statusCode, string $responseBody, array $headers = [])`,
  so passing a `null` body throws.

### `null` is rejected where parameters are non-nullable

Public setters carry native types, so passing `null` (or a non-coercible value) to
a non-nullable parameter throws `TypeError`. Most likely to affect you:

- `PaymentWindow::setGatewayId/setCurrency/setAmount/setReference/setAcceptUrl/setType/setMethod/setLanguage/setDeclineUrl/setCallbackUrl/setDesign/setSecret/setPlatform` — pass a value, not `null`.
- `CartItem::__construct(string $name, int $price, int $quantity, int $tax, …)` — the first four are required and non-null.

<!--
Template for a new entry:

### <Short title of the change>

<What changed, and who is affected.>
-->
