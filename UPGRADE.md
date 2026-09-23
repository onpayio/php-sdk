# Upgrade to 2.0

## Requirements

- PHP 8.2 or later.
- The SDK now depends on `psr/http-client`, `psr/http-factory`, `psr/http-message`,
  `php-http/discovery`, `psr/log` (`^1.1 || ^2.0 || ^3.0`) and `league/oauth2-client` (`^2.9`).

## The bundled cURL client is replaced by a PSR-18 client

The SDK no longer ships an HTTP client. Install a PSR-18 client with PSR-17 factories, for
example `composer require guzzlehttp/guzzle`. It is discovered automatically, or pass it to
the `OnPayAPI` constructor. Without one the constructor throws `\InvalidArgumentException`.

Timeouts, proxies and TLS settings are those of your client. A Guzzle client the SDK
discovers itself is created with the 1.x timeouts (30 seconds total, 5 seconds to connect).
An injected client is used as you configured it.

## The vendored OAuth client is removed

`OnPay\OAuth\Client\*`, `OnPay\CurlHttpClientLogger` and `OnPay\Session` no longer exist.
OAuth runs on `league/oauth2-client` internally.

- Tokens stored by 1.x keep working, including refresh, and are re-saved in the new format
  on first use. No re-authorization is needed.
- `finishAuthorize()` throws `OnPay\API\Exception\TokenException` or `ConnectionException`.
  In 1.x it let the removed `OnPay\OAuth\Client\Exception\*` and `CurlException` classes
  escape, so a `catch` around the OAuth callback that names those classes no longer matches.
  `get()`/`post()` already threw `TokenException`/`ConnectionException`; only `getPrevious()`
  changed there.

## `TokenStorageInterface` is typed

```diff
-public function getToken();
+public function getToken(): ?string;
-public function saveToken($token);
+public function saveToken(string $token);
```

An implementation whose `getToken()` declares no return type fails to load. Add `: ?string`.
An untyped `saveToken($token)` still loads, but should declare `string $token`.

## Parameters and return values are typed

Public methods carry native types. What this changes for a caller:

- Passing `null` to a non-nullable parameter throws `TypeError`. Most likely to have received
  `null` for "not set": the `PaymentWindow` string setters (`setGatewayId()`, `setCurrency()`,
  `setAmount()`, `setReference()`, `setAcceptUrl()`, `setDeclineUrl()`, `setCallbackUrl()`,
  `setType()`, `setMethod()`, `setLanguage()`, `setDesign()`, `setSecret()`, `setPlatform()`),
  its `bool` setters (`set3DSecure()`, `setSurchargeEnabled()`,
  `setSubscriptionWithTransaction()`), `setSurchargeVatRate()`, the first four `CartItem`
  constructor arguments, `finishAuthorize($code)` and the `$direction` argument of
  `getTransactions()`/`getSubscriptions()`. Skip the call instead.
- Under `declare(strict_types=1)` a scalar of the wrong type is no longer coerced:
  `setAmount(12345)` needs a string, `captureTransaction($transaction->transactionNumber)`
  needs `(string)` because the property is an `int`, `createTransactionFromSubscription()`
  needs an `int` amount and a `string` order id, and `getTransactions('1')` needs an `int`.
- `setSubscriptionWithTransaction()` takes `bool`. In 1.x only `true` enabled the flag; in
  weak mode a truthy non-bool such as `1` now enables it too.
- The `OnPayAPI` options `client_id`, `redirect_uri`, `base_uri` and `base_authorize_uri`
  must be strings; an `int` or an explicit `null` throws `\InvalidArgumentException`. A
  non-string `platform` falls back to the SDK's default instead of being sent as-is.

## `PaymentService` is constructed through the facade

The service constructors take an internal `ApiClient` and are `@internal`.

```diff
-$payment = new \OnPay\API\PaymentService($api);
+$payment = $api->payment();
```

## Classes are `final`, internals are `@internal`

Every class in `src/` is `final` except `Transaction\SimpleTransaction`,
`Subscription\SimpleSubscription`, the abstract `Exception\OnPayException` and
`PaymentMethodAbstract`, and the two interfaces you implement (`TokenStorageInterface`,
`AuthStateStorageInterface`). Non-public members are `private`; the `protected` members of
`OnPayAPI` (`$tokenStorage`, `$client`, `$httpClient`, `$request`, `$response`,
`getClient()`, …) are gone, and `PaymentInfo`'s field properties are only reachable through
its setters and `getFields()`.

If you extended an SDK class, wrap it instead. If you mocked a service class in your tests,
put your own interface in front of the SDK and mock that.

Anything marked `@internal` (the `Http\*`, `Auth\*`, `Log\*` and `OAuth\*` namespaces, the
value-object constructors, the `Http\Request`/`Http\Response` setters) may change in a
patch release.

## The 1.x deprecations are removed

| Removed | Use instead |
| --- | --- |
| `PaymentWindow::setSecureEnabled(bool)` | `PaymentWindow::set3DSecure(bool)` |
| `PaymentWindow::hasSecureEnabled()` | `PaymentWindow::is3DSecure()` |
| `Transaction\CardholderData::$street`, `$number`, `$floor`, `$door` | `$address1`, `$address2` |
| `Transaction\CardholderData::$deliveryStreet`, `$deliveryNumber`, `$deliveryFloor`, `$deliveryDoor` | `$deliveryAddress1`, `$deliveryAddress2` |

Reading a removed property raises `Warning: Undefined property` and yields `null` rather
than failing loudly, so grep for the eight names.

## `Currencies::isValidISO4217()` takes an `int`

The parameter was `int|string`, but a numeric string never matched. It is now `int`, which
is how the API sends `currency_code`. Under `declare(strict_types=1)`,
`isValidISO4217('208')` throws `\TypeError`: cast first. `Util\Currency` is unchanged.

## Failed requests are no longer dumped to `error_log()`

1.x wrote the full request and response, including the `Authorization` header, to
`error_log()` on every non-2xx response. 2.0 logs through PSR-3 instead: a one-line
warning (4xx) or error (5xx, transport failure) with credentials redacted, to the logger
passed as the seventh constructor argument, or to `error_log()` when none is given. Pass a
`Psr\Log\NullLogger` to silence it.

## OAuth without `AuthStateStorageInterface` is deprecated

`authorize()` and `finishAuthorize()` can now verify the CSRF `state` and use PKCE. Implement
`OnPay\AuthStateStorageInterface`, pass it as the third constructor argument, and pass the
returned `state` to `finishAuthorize()`:

```diff
-$api = new OnPayAPI($tokenStorage, $options);
+$api = new OnPayAPI($tokenStorage, $options, $authStateStorage);
 $redirectUrl = $api->authorize();
-$api->finishAuthorize($_GET['code']);
+$api->finishAuthorize($_GET['code'], $_GET['state']);
```

Without the storage the flow works as in 1.x, with neither `state` verification nor PKCE,
and both methods emit an `@`-suppressed `E_USER_DEPRECATED`. An error handler that throws
on every deprecation without checking `error_reporting()` will throw there. `StaticToken`
users are unaffected.

## Deprecated payment-method constants

`OnPay\API\Enum\PaymentMethod` is the single source of payment-method identifiers.
`PaymentWindow::METHOD_*`, `Util\PaymentMethods\Enums\Methods::*` and the method classes'
`METHOD_NAME` constants keep their values but are deprecated.

```diff
-$window->setMethod(\OnPay\API\PaymentWindow::METHOD_CARD);
+$window->setMethod(\OnPay\API\Enum\PaymentMethod::CARD);
```

`setMethod()` still accepts a string, which is passed through unvalidated, and
`getMethod()` still returns the raw string.

## Deprecated `PaymentWindow::DELIVERY_DISABLED_*` constants

Use `OnPay\API\Enum\DeliveryDisabled`.

```diff
-$window->setDeliveryDisabled(\OnPay\API\PaymentWindow::DELIVERY_DISABLED_NOT_PHYSICAL);
+$window->setDeliveryDisabled(\OnPay\API\Enum\DeliveryDisabled::NOT_PHYSICAL);
```

## Deprecated `PaymentWindow` method names

| Deprecated | Use instead |
| --- | --- |
| `isSurcharge_enabled()` | `isSurchargeEnabled(): ?bool` |
| `setTestMode($mixed)` | `setTestModeEnabled(bool)` |
| `getTestMode()` | `isTestModeEnabled(): bool` |
