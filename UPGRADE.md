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

### Bring your own HTTP client (PSR-18)

The bundled cURL client is gone. The SDK sends all traffic through a
[PSR-18](https://www.php-fig.org/psr/psr-18/) client and
[PSR-17](https://www.php-fig.org/psr/psr-17/) factories: pass them to the
`OnPayAPI` constructor, or install one (e.g. `guzzlehttp/guzzle`) and the SDK
discovers it. Without either, the constructor throws `\InvalidArgumentException`.

Timeouts, proxies and TLS settings are now those of your client — the SDK no
longer applies its own 30-second timeout.

### The vendored OAuth client is removed

`OnPay\OAuth\Client\*`, `OnPay\CurlHttpClientLogger` and `OnPay\Session` no
longer exist; OAuth is handled by `league/oauth2-client` internally.

- Tokens stored by 1.x keep working, including refresh. On first use they are
  re-saved through your `TokenStorageInterface` in the new, shorter format.
- Errors are still `TokenException`/`ConnectionException`, but their messages
  now include the underlying reason, and `getPrevious()` no longer returns an
  `OnPay\OAuth\Client\Exception\*` instance.

### Public method signatures are now typed (Psalm level 1)

Most public methods gained native parameter and return types, and several
returns became nullable to reflect values they could always return:

- `OnPay\API\Http\Response::getStatusCode()` returns `?int` (was documented `string`);
  `Request`/`Response` getters (`getMethod`/`getUri`/`getBody`) return `?string`.
- `OnPayAPI::get()`/`post()` return `array`; `getPlatform()` returns `?string`;
  `getLastHttpRequest()`/`getLastHttpResponse()` are nullable.
- `TransactionCollection::$pagination` and `SubscriptionCollection::$pagination` are `?Pagination`.
- `StaticToken::getToken()` takes no arguments and returns `?string`.
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
- A stored token that is not valid JSON, has no `access_token`, or (1.x format)
  has an unparseable `issued_at` throws `TokenException` when it is read.

Well-formed API responses and tokens behave exactly as before.

### `TokenStorageInterface` now requires native types

Your `OnPay\TokenStorageInterface` implementation must declare matching native
types, or the class fatals at load: `getToken(): ?string`, `saveToken(string $token)`.

### `null` is rejected where parameters are non-nullable

Public setters carry native types, so passing `null` (or a non-coercible value) to
a non-nullable parameter throws `TypeError`. Most likely to affect you:

- `PaymentWindow::setGatewayId/setCurrency/setAmount/setReference/setAcceptUrl/setType/setMethod/setLanguage/setDeclineUrl/setCallbackUrl/setDesign/setSecret/setPlatform` — pass a value, not `null`.
- `CartItem::__construct(string $name, int $price, int $quantity, int $tax, …)` — the first four are required and non-null.

### OAuth `state` (CSRF) verification and PKCE

The OAuth authorization flow can now verify the CSRF `state` and use PKCE. To enable
both, implement `OnPay\AuthStateStorageInterface` and pass it as the seventh argument
to the `OnPayAPI` constructor. It stores the `state` and PKCE `code_verifier` that
`authorize()` generates so `finishAuthorize()` can verify the callback:

```php
$api = new OnPayAPI($tokenStorage, $options, null, null, null, null, $authStateStorage);

// Build the redirect (state + verifier are saved via the storage):
$redirectUrl = $api->authorize();

// On the callback, pass the returned state so it is verified:
$api->finishAuthorize($_GET['code'], $_GET['state']);
```

A mismatched or missing `state` throws `OnPay\API\Exception\TokenException` before any
token is exchanged. The storage is cleared once the flow completes.

**Backwards compatibility:** the new constructor argument and the second
`finishAuthorize()` argument are both optional. When no `AuthStateStorageInterface` is
supplied, the flow behaves as before (no `state` verification, no PKCE) but now emits an
`E_USER_DEPRECATED` notice from `authorize()` and `finishAuthorize()`. This is silent
under PHP's default error handler, but **a project whose `set_error_handler` escalates
`E_USER_DEPRECATED` to an exception will now throw** when building the authorize URL or
finishing authorization — wire up an `AuthStateStorageInterface` to resolve it (and gain
the CSRF/PKCE protection).

### `PaymentService` is constructed only via the facade

`OnPayAPI` no longer builds and sends requests itself; that work moved to internal
collaborators (`OnPay\Http\ApiClient`, `OnPay\Auth\TokenManager`). The public facade
surface is unchanged, but the API service classes are now constructed with an
`OnPay\Http\ApiClient` instead of `OnPayAPI`.

Only `OnPay\API\PaymentService` is affected in practice: in 1.x its constructor was the
one service constructor not marked `@internal`, and the 1.x README showed it being
constructed directly. It is now `@internal` and takes an `ApiClient`. Construct it
through the facade, not with `new`:

```php
// Before (1.x)
$payment = new \OnPay\API\PaymentService($onPayApi);
// After (2.0)
$payment = $onPayApi->payment();
```

`TransactionService`, `SubscriptionService` and `GatewayService` were already
`@internal` in 1.x, so their equivalent change breaks no supported usage. All four
service classes and their methods remain part of the public API — only constructing
them directly is unsupported.

### `OnPayAPI` is `final`

`OnPayAPI` can no longer be extended. A class declared `extends OnPayAPI` fatals at
load. Its public methods are the supported surface; wrap or compose the facade instead
of subclassing it.

This formalises what the refactoring above already did: the `protected` members a 1.x
subclass could have reached (`$tokenStorage`, `$oauth2Provider`, `$client`, `$httpClient`,
`$scope`, `$userId`, `$platform`, `$request`, `$response` and the `getClient()` method)
no longer exist on `OnPayAPI`. The token and HTTP internals now live in the `@internal`
`OnPay\Auth\TokenManager` and `OnPay\Http\ApiClient` classes and are not part of the
public API.

### Payment methods are a PHP enum

The payment-method identifiers were defined twice — `PaymentWindow::METHOD_*` and the
`OnPay\API\Util\PaymentMethods\Enums\Methods` class of string constants. Both are now
defined in terms of a real enum, `OnPay\API\Enum\PaymentMethod`, which is the single
source of truth:

```php
// Before (1.x, still works but deprecated)
$paymentWindow->setMethod(\OnPay\API\PaymentWindow::METHOD_CARD);
$paymentWindow->setMethod(\OnPay\API\Util\PaymentMethods\Enums\Methods::CARD);
// After (2.0)
$paymentWindow->setMethod(\OnPay\API\Enum\PaymentMethod::CARD);
```

Nothing breaks for callers:

- Both old constant sources are kept and `@deprecated`, with exactly their current values
  (`PaymentWindow::METHOD_CARD === PaymentMethod::CARD->value === 'card'`). Your IDE and
  static analyser will flag them; the values on the wire are identical.
- `PaymentWindow::setMethod()`, `PaymentMethods::getCurrenciesByMethod()` and
  `Currency::isPaymentMethodAvailable()` now take `string|PaymentMethod`. A method passed as
  a string is **not** validated against the enum, because the gateway can offer a method
  before this SDK lists it — an unknown identifier is passed through exactly as in 1.x.
- `PaymentWindow::getMethod()` still returns the raw `?string` sent to the gateway, not an
  enum case.

The one genuine break is for code that *implements* `PaymentMethodInterface` itself: the
interface gained `getMethod(): PaymentMethod` alongside the unchanged `getName(): string`,
so such an implementation must add the method. The SDK's own (`@internal`) method classes
implement it, and their `METHOD_NAME` constants are deprecated in favour of `getMethod()`.

Currencies and languages were reviewed for the same duplication and deliberately left
alone. `Util\PaymentMethods\Enums\CurrencyCodes` is not a duplicate definition: it only
names the keys of `Util\Currencies::CURRENCIES`, which stays the single source of supported
currencies, and it carries the `ALL_CURRENCY_CODES` sentinel, which is not a currency — so
it is not an enum and keeps its constants. The SDK has no language constants at all
(`PaymentWindow::setLanguage()` takes a free-form string), so there was nothing to
consolidate.

### Delivery-disabled reasons are a PHP enum

The payment window's `delivery_disabled` reasons are a closed set, so they are now an enum,
`OnPay\API\Enum\DeliveryDisabled`, and `PaymentWindow::setDeliveryDisabled()` takes
`string|DeliveryDisabled|null`:

```php
// Before (1.x, still works but deprecated)
$paymentWindow->setDeliveryDisabled(\OnPay\API\PaymentWindow::DELIVERY_DISABLED_NOT_PHYSICAL);
// After (2.0)
$paymentWindow->setDeliveryDisabled(\OnPay\API\Enum\DeliveryDisabled::NOT_PHYSICAL);
```

The five `PaymentWindow::DELIVERY_DISABLED_*` constants are kept and `@deprecated` with
their current values, a string is still passed through unvalidated, `null` still clears the
field, and `getDeliveryDisabled()` still returns `?string`. Nothing breaks.

<!--
Template for a new entry:

### <Short title of the change>

<What changed, and who is affected.>
-->
