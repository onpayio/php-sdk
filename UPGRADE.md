# Upgrading from 1.x to 2.0

Every backwards-incompatible change in the `2.0` series is recorded here as it
lands. If you are upgrading, read this document top to bottom and check each
change against the code you use.

## Requirements

`2.0` requires **PHP 8.2 or later**. Support for PHP 7.4, 8.0, and 8.1 has been
dropped.

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
(non-`final`) classes and overrides these methods is affected: overrides must now
use a compatible signature.

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

### Failed requests are logged through PSR-3 (error_log() by default), with secrets redacted

**What changed:** 1.x wrote the *full* request and response of every non-2xx reply to
`error_log()`, bearer tokens and card data included. 2.0 replaces that with optional
PSR-3 logging. `OnPayAPI` takes a `Psr\Log\LoggerInterface` as an optional sixth
constructor argument:

- Non-2xx responses are logged at `warning` (4xx) or `error` (5xx); transport failures at
  `error`. These records carry the method, URI, status and the request/response headers and
  body **after redaction**: `Authorization`/`Proxy-Authorization`/`Cookie`/`Set-Cookie`
  headers, OAuth material (`access_token`, `refresh_token`, `code`, `client_secret`, …) and
  cardholder data (`card_number`/`pan`, `cvc`/`cvv`, expiry fields, plus any string that is
  a Luhn-valid 13–19 digit number, whatever its key) are replaced with `[redacted]`. Bodies
  that are neither JSON nor form-encoded are omitted altogether.
- Successful round trips are logged at `debug` with method, URI and status only.
- When no logger is given, the SDK falls back to `OnPay\Log\ErrorLogLogger`, which writes
  `warning` and above to `error_log()` prefixed `[OnPay SDK]`.

**Why:** operators lost all failure visibility when the leaking `error_log()` call was
removed; this brings it back in a form that can be routed into the application's own
logging stack and can no longer expose credentials or cardholder data.

**Impact:** with no code change you keep getting failures in the PHP error log, but they
are now one redacted line per failed request instead of the raw dump. The
`getLastHttpRequest()` / `getLastHttpResponse()` debug accessors are unchanged and still
expose the unredacted wire data.

**How to migrate:** to route SDK logs into your application's logger, pass it in:

```php
$api = new OnPay\OnPayAPI($tokenStorage, $options, $httpClient, $factory, $factory, $logger);
```

To silence the SDK completely, pass `new \Psr\Log\NullLogger()`. To keep the
`error_log()` fallback but also see successful requests, pass
`new \OnPay\Log\ErrorLogLogger(\Psr\Log\LogLevel::DEBUG)`.

<!--
Template for a new entry:

### <Short title of the change>

<What changed, and who is affected.>
-->
