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
- The method classes returned by `Currency::getPaymentMethods()` and
  `PaymentMethods::getAllPaymentMethods()` expose `getMethod(): PaymentMethod` alongside the
  unchanged `getName(): string`; their `METHOD_NAME` constants are deprecated in favour of
  `getMethod()`. `PaymentMethodInterface` is implemented only by the SDK's own method classes
  and is not an extension point.

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

### Inconsistently named `PaymentWindow` methods have properly named replacements

Three spots on `PaymentWindow` did not match the rest of its method surface. All three
keep working — the old names are now `@deprecated` aliases — but new code should use the
replacements:

| Deprecated | Use instead |
| --- | --- |
| `isSurcharge_enabled()` | `isSurchargeEnabled(): ?bool` |
| `setTestMode($mixed)` | `setTestModeEnabled(bool $enabled): void` |
| `getTestMode()` | `isTestModeEnabled(): bool` |

`isSurcharge_enabled()` was the SDK's only method mixing snake_case and camelCase; the
setter `setSurchargeEnabled()` was already correct. The new getter returns the same
`?bool`, including `null` when the flag was never set.

Test mode was `PaymentWindow`'s one untyped setter — `setTestMode()` accepted anything and
`getTestMode()` returned `int|bool|string|null`, so nothing in the signature said test mode
is a flag. The typed pair does:

```php
// Before (1.x, still works but deprecated)
$paymentWindow->setTestMode(true);
// After (2.0)
$paymentWindow->setTestModeEnabled(true);
```

`isTestModeEnabled()` also reads a value stored through the deprecated setter —
`setTestMode('yes')` then `isTestModeEnabled() === true`.

`PaymentWindow` had two further misnamed methods, `setSecureEnabled()`/`hasSecureEnabled()`.
Those were already `@deprecated` in 1.x and 2.0 removes them outright rather than renaming
them again — see [The 1.x deprecations are removed](#the-1x-deprecations-are-removed).

### The 1.x deprecations are removed

Everything that already carried a `@deprecated` tag in the 1.x branch is gone in 2.0. It
had a documented replacement for years; the tags are not renewed.

| Removed | Use instead |
| --- | --- |
| `PaymentWindow::setSecureEnabled(bool)` | `PaymentWindow::set3DSecure(bool)` |
| `PaymentWindow::hasSecureEnabled()` | `PaymentWindow::is3DSecure()` |
| `Transaction\CardholderData::$street` | `$address1` / `$address2` |
| `Transaction\CardholderData::$number` | `$address1` / `$address2` |
| `Transaction\CardholderData::$floor` | `$address1` / `$address2` |
| `Transaction\CardholderData::$door` | `$address1` / `$address2` |
| `Transaction\CardholderData::$deliveryStreet` | `$deliveryAddress1` / `$deliveryAddress2` |
| `Transaction\CardholderData::$deliveryNumber` | `$deliveryAddress1` / `$deliveryAddress2` |
| `Transaction\CardholderData::$deliveryFloor` | `$deliveryAddress1` / `$deliveryAddress2` |
| `Transaction\CardholderData::$deliveryDoor` | `$deliveryAddress1` / `$deliveryAddress2` |

The two `PaymentWindow` methods were one-line aliases, so swapping the names over is a
mechanical change with no behavioural difference.

**The `CardholderData` properties are different, and worth reading carefully: this is a
removal of data access, not a cleanup of dead code.** The OnPay API still sends the split
address components — `street`, `number`, `floor` and `door`, in both the billing block and
`delivery_address` — and 1.x surfaced them verbatim. 2.0 stops reading those keys, so the
SDK no longer exposes information that is still on the wire. If your integration read the
components individually (to re-render an address, or to feed a shipping system that wants
street and house number apart), the `address1`/`address2` pair is what you get from now on
and you will have to split it yourself, or read the raw payload:

```php
// Before (1.x)
$street = $transaction->cardholderData->street;
$number = $transaction->cardholderData->number;

// After (2.0) — the composed lines
$line1 = $transaction->cardholderData->address1; // e.g. "Hovedgaden 1"
$line2 = $transaction->cardholderData->address2;

// After (2.0) — still on the wire, if you genuinely need the components
$raw = $onPayAPI->get('transaction/' . $transactionNumber);
$street = $raw['data']['cardholder_data']['street'] ?? null;
```

Reading a removed property now raises `Warning: Undefined property` (and yields `null`)
rather than failing loudly, so grep your code for the eight names rather than relying on
the runtime to find them for you.

### Internal-by-default: `final` classes and tightened visibility

2.0 makes the SDK's public API contract explicit and small: a class or member is part of it
only when it is deliberately meant to be consumed. Everything else is marked `@internal`,
`final`, or `private`. Nothing here changes runtime behaviour — **the only code affected is
code that extends an SDK class, overrides one of its methods, or redeclares one of its
properties.** If you only construct and call the SDK, this section does not apply to you.

Psalm's `ClassMustBeFinal` check is no longer suppressed in `psalm.xml`, so the rule is
enforced in CI rather than merely documented.

#### Classes that are now `final`

48 classes, i.e. every class in `src/` that is neither abstract nor extended by the SDK
itself:

- **Payment window builders:** `API\PaymentWindow`, `API\PaymentWindow\Cart`,
  `API\PaymentWindow\CartItem`, `API\PaymentWindow\CartShipping`,
  `API\PaymentWindow\CartHandling`, `API\PaymentWindow\PaymentInfo`.
- **API services:** `API\TransactionService`, `API\SubscriptionService`,
  `API\PaymentService`, `API\GatewayService`.
- **Response value objects (leaves):** `API\Transaction\DetailedTransaction`,
  `API\Transaction\CardholderData`, `API\Transaction\TransactionHistory`,
  `API\Transaction\TransactionCollection`, `API\Subscription\DetailedSubscription`,
  `API\Subscription\SubscriptionHistory`, `API\Subscription\SubscriptionCollection`,
  `API\Payment\SimplePayment`, `API\Gateway\Information`,
  `API\Gateway\PaymentWindowIntegrationSettings`,
  `API\Gateway\SimplePaymentWindowDesign`, `API\Gateway\PaymentWindowDesignCollection`,
  `API\Util\Pagination`, `API\Util\Link`, `API\Http\Request`, `API\Http\Response`.
- **Exceptions:** `API\Exception\ApiException`, `ConnectionException`, `TokenException`,
  `InvalidFormatException`, `MissingDataException`, `InvalidCartException`.
- **Helpers:** `API\Util\Currency`, `API\Util\Converter`, `API\Util\DataReader`,
  `API\Util\PaymentMethods\PaymentMethods`.
- **Transport, OAuth and logging internals:** `Http\ApiClient`, `Http\Psr18HttpClient`,
  `Http\LoggingHttpClient`, `Http\GuzzleClientAdapter`, `Auth\TokenManager`,
  `InternalTokenStorage`, `StaticToken`, `OAuth\OnPayProvider`,
  `OAuth\OnPayOptionProvider`, `OAuth\Psr17RequestFactory`, `Log\ErrorLogLogger`,
  `Log\Redactor`.

(`OnPayAPI`, `API\Util\Currencies`, `Http\MessageUtil`, the eleven concrete payment-method
classes and the two constant holders were already `final` earlier in the 2.0 series.)

**What is still open, and why:**

- `API\Transaction\SimpleTransaction` and `API\Subscription\SimpleSubscription` —
  `DetailedTransaction`/`DetailedSubscription` extend them.
- `API\Exception\OnPayException` and
  `API\Util\PaymentMethods\Methods\PaymentMethodAbstract` — abstract bases.
- `TokenStorageInterface` and `AuthStateStorageInterface` — interfaces you are meant to
  implement. They are not `@internal` and they are not going anywhere.
- `API\Util\PaymentMethods\Methods\PaymentMethodInterface` is the read-side type of the
  objects returned by `Currency::getPaymentMethods()` and
  `PaymentMethods::getAllPaymentMethods()`. It is now `@internal` like the classes that
  implement it: call `getMethod()`/`getName()`/`getCurrencies()` on what the SDK hands you,
  but do not implement it yourself.

If you extended one of the now-`final` classes, wrap it instead of inheriting from it: hold
the SDK object as a property and expose your own methods. The same applies to test doubles —
a `final` class cannot be mocked by PHPUnit, so if you mocked `TransactionService` (or any
other service) in your own tests, put your own interface in front of the SDK and mock that.
`OnPayAPI` has been `final` since earlier in the 2.0 series, so that seam is likely already
where you need it.

#### Members that lost visibility

- `API\PaymentWindow\PaymentInfo`: all 42 field properties (`$account_id`,
  `$billing_address_*`, `$shipping_address_*`, `$phone_*`, `$delivery_*`, …) plus
  `$availableFields` and `validateField()` are `private` (were `protected`). The payload is
  built and read through the setters and `getFields()`/`getFieldsWithoutPrefix()`.
- `API\Http\Request`: `$method`, `$uri`, `$headers`, `$body` are `private`.
  `API\Http\Response`: `$statusCode`, `$body` are `private`. The getters are unchanged and
  remain the supported way to inspect `OnPayAPI::getLastHttpRequest()`/`getLastHttpResponse()`;
  the setters are now `@internal` — only the SDK fills these objects.
- `StaticToken`: `$staticToken` is `private`.
- `OAuth\OnPayProvider`: `$urlAuthorize`, `$urlAccessToken` and `$pkceEnabled` are
  `private`. Its five `protected` method overrides stay `protected`, since
  `league/oauth2-client`'s `AbstractProvider` declares them that way.

#### Members that were removed as internal plumbing

- `Http\LoggingHttpClient::getInnerClient()` — a public accessor on an `@internal` class
  that nothing outside the SDK's own tests ever called.

#### Newly `@internal`

`@internal` marks code the SDK may change in a patch release. Most of the internals were
already annotated earlier in the 2.0 series; this release adds the stragglers:

- the `API\Http\Request` / `API\Http\Response` setters,
- `API\Util\Converter` (the API date-format parser),
- the `API\Util\Link`, `API\Payment\SimplePayment` and
  `API\Exception\InvalidCartException` constructors.

The classes themselves stay public where consumers legitimately receive instances of them —
only the SDK constructs or fills them.

<!--
Template for a new entry:

### <Short title of the change>

<What changed, and who is affected.>
-->
