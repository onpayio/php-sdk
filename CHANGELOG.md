# Changelog
All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]
- BREAKING: Dropped support for PHP < 8.2; the SDK now requires PHP 8.2 or later. See UPGRADE.md.
- Added: Psalm static analysis (errorLevel 1, `src/`) runs in CI; `src/` is now fully typed and JSON responses are narrowed through a typed reader.
- BREAKING: most public methods gained native parameter/return types and several getters/returns became nullable to reflect the values they can return (e.g. `Http\Response::getStatusCode(): ?int`, `Http` `Request`/`Response` getters `: ?string`, `OnPayAPI::get()/post(): array`, `getPlatform(): ?string`, `Transaction`/`Subscription` `Collection::$pagination: ?Pagination`, `StaticToken::getToken(): ?string`). These are BC only for code that subclasses these (non-final) classes and overrides those methods. See UPGRADE.md.
- Changed: stricter validation — a `200` whose body is not a JSON object now throws `ApiException` (was silently treated as empty); a response missing a field the API always returns (e.g. a transaction's `uuid`/`amount`/`created`) now throws `ApiException` instead of yielding a value object with `null` fields; a stored token that is not valid JSON or lacks `access_token` throws `TokenException`. See UPGRADE.md.
- BREAKING: the bundled cURL client is removed and the SDK no longer ships or requires an HTTP client. `OnPayAPI` accepts an injectable PSR-18 client with PSR-17 factories; when none is given they are auto-discovered (php-http/discovery) from the installed packages, and construction throws `\InvalidArgumentException` if nothing is found. `getLastHttpRequest()`/`getLastHttpResponse()` are preserved. See UPGRADE.md.
- BREAKING: OAuth now runs on `league/oauth2-client` through `OnPay\OAuth\OnPayProvider`; the vendored `OnPay\OAuth\Client\*` fork of fkooman/oauth2-client (and its LICENSE), `OnPay\CurlHttpClientLogger` and `OnPay\Session` are removed. Tokens stored by 1.x are read transparently (expiry = `issued_at + expires_in`, refresh token kept) and re-saved in league's format on first use, so no re-authorization is needed. `TokenException` messages now include the underlying reason. See UPGRADE.md.
- Security: non-2xx replies are no longer `error_log()`ed with the full request/response (which leaked bearer tokens and request bodies).
- Added: optional PSR-3 logging. `OnPayAPI` accepts a `Psr\Log\LoggerInterface` as its sixth constructor argument; failed API/OAuth round trips are logged at warning (4xx) / error (5xx, transport failure) and successful ones at debug. Without a logger, warnings and errors go to `error_log()` as in 1.x. The Authorization header, OAuth tokens/codes and the payment window secret are redacted before logging; non-JSON/form bodies are omitted.
- BREAKING: every `src/` file now declares `strict_types=1` and the SDK's `TokenStorageInterface` carries native types; classes that implement it must declare compatible types. Public methods gained native parameter types: in strict mode a mismatched scalar raises `TypeError`, and in any mode passing `null` to a now non-nullable parameter (e.g. the `PaymentWindow` setters) raises `TypeError`. See UPGRADE.md.
- Changed: JSON handling hardened with `JSON_THROW_ON_ERROR` (+ `JSON_UNESCAPED_SLASHES` on encode). `Json::encode(null)` now returns `"null"` instead of throwing; `OnPayAPI::post()` throws `ApiException` on an unencodable body (was: sent a null body); `StaticToken`/`InternalTokenStorage` throw a typed exception on a corrupt stored token; `OnPayAPI::post()` now maps `AccessTokenException` to `TokenException`, matching `get()`. See UPGRADE.md.
- Fixed: `TransactionHistory` and `SubscriptionHistory` now read `uuid` (always present and non-null on the wire) as a required field, and `DetailedSubscription` reads `card_mask` (present but nullable) — all previously dropped; removed the deprecated `curl_close()` no-op along with the now-empty `CurlHttpClient::__destruct()` (a subclass calling `parent::__destruct()` now fatals) and made implicitly-nullable constructor params explicitly nullable (clears PHP 8.4+ deprecations); corrected the `OAUth` catch-casing typo and stale Guzzle `@throws` docblocks; `InvalidCartException::$errors` is now a typed `array`.
- Security: `PaymentWindow::validatePayment()` now matches the signed `onpay_` fields by prefix (`str_starts_with`) instead of a loose substring check, so a field whose name merely contains `onpay_` (e.g. `foo_onpay_bar`) is no longer folded into the verified set; the HMAC is compared with `hash_equals()` (timing-safe) instead of `===`.
- Added: OAuth `state` (CSRF) verification and PKCE. Implement `OnPay\AuthStateStorageInterface` and pass it as the seventh `OnPayAPI` constructor argument to persist the `state` and PKCE `code_verifier` across the redirect; `finishAuthorize()` gains an optional second argument for the returned `state` and throws `TokenException` on a mismatch before any token exchange. See UPGRADE.md.
- Deprecated: using the OAuth flow without an `AuthStateStorageInterface` triggers `E_USER_DEPRECATED` from `authorize()`/`finishAuthorize()` (no `state` verification, no PKCE); a `set_error_handler` that escalates deprecations will throw. See UPGRADE.md.
- Changed: the monolithic `OnPayAPI` was split into a facade plus two internal collaborators — `OnPay\Http\ApiClient` (the authenticated API call path and the `getLastHttpRequest()`/`getLastHttpResponse()` debug capture) and `OnPay\Auth\TokenManager` (the OAuth authorize flow and token lifecycle). The public `OnPayAPI` method surface is unchanged.
- BREAKING: the API service classes (`TransactionService`, `SubscriptionService`, `PaymentService`, `GatewayService`) are now constructed with an `OnPay\Http\ApiClient` instead of `OnPayAPI`, and all four constructors are `@internal`. Only `PaymentService` is a practical break — it was the one service constructor not marked `@internal` in 1.x; construct it via `$onPayAPI->payment()`. The service classes and their methods remain public API. See UPGRADE.md.
- BREAKING: `OnPayAPI` is now `final`. The `protected` members a 1.x subclass could reach (`$tokenStorage`, `$oauth2Provider`, `$client`, `$httpClient`, `$scope`, `$userId`, `$platform`, `$request`, `$response`, `getClient()`) no longer exist; the internals live in `OnPay\Auth\TokenManager` and `OnPay\Http\ApiClient`. See UPGRADE.md.
- Added: a common abstract base exception, `OnPay\API\Exception\OnPayException` (extends `\Exception`). Every runtime error the SDK raises — `ApiException`, `ConnectionException`, `TokenException`, `InvalidFormatException`, `MissingDataException`, `InvalidCartException` — now extends it, so a single `catch (OnPayException $e)` handles any SDK-originated error. Misconfiguration and misuse (e.g. invalid `OnPayAPI` constructor options) still throw SPL `\InvalidArgumentException` / `\LogicException` and are not part of this hierarchy. This is additive: each exception is still an `\Exception`, and existing per-subclass catch sites are unchanged.
- Changed: the OAuth token-endpoint failure mapping is now applied in one place (`Auth\TokenManager::requestAccessToken()`), so both the authorization-code exchange and the refresh grant map a transport failure to `ConnectionException` and a protocol/parse failure to `TokenException`, consistent with the API call path. Observable exception types are unchanged.
- Added: `OnPay\API\Enum\PaymentMethod`, a real PHP enum that is now the single source of truth for the payment-method identifiers. `PaymentWindow::setMethod()`, `Util\PaymentMethods\PaymentMethods::getCurrenciesByMethod()` and `Util\Currency::isPaymentMethodAvailable()` accept `string|PaymentMethod`, and the method classes expose `getMethod(): PaymentMethod`. Unknown method strings are still accepted unvalidated, so a method the gateway adds before the SDK does keeps working. See UPGRADE.md.
- Deprecated: the two old payment-method constant sources, `PaymentWindow::METHOD_*` and the `Util\PaymentMethods\Enums\Methods` class (plus each method class's `METHOD_NAME`). All keep their current values and are now defined in terms of `Enum\PaymentMethod`; use the enum instead. See UPGRADE.md.
- BREAKING: `Util\PaymentMethods\Methods\PaymentMethodInterface` gained `getMethod(): PaymentMethod`. The SDK's own method classes implement it; a (very unlikely) third-party implementation of the interface must add the method. `getName(): string` is unchanged. See UPGRADE.md.
- Added: `OnPay\API\Enum\DeliveryDisabled`, an enum for the payment window's `delivery_disabled` reasons; `PaymentWindow::setDeliveryDisabled()` accepts `string|DeliveryDisabled|null`. The five `PaymentWindow::DELIVERY_DISABLED_*` constants are kept as `@deprecated` aliases with unchanged values. See UPGRADE.md.

## [1.0.39] - 2026-09-01
- Accept alphanumeric gateway_id in OnPayAPI constructor

## [1.0.38] - 2026-04-29
- Fixed broken 1.0.37 release

## [1.0.37] - 2026-04-29
- Added SECURITY.md
- Add cardMask property to DetailedTransaction

## [1.0.36] - 2025-10-29
- Bumped minimum version of PHP supported, to 7.4
- Fixed typing bug in PaymentWindow object

## [1.0.35] - 2025-10-01
- Add input validation for transaction identifiers to prevent invalid API calls
- Add input validation and unit tests for SubscriptionService methods
- Added new cart parameters to payment creation objects

## [1.0.34] - 2025-03-07
- Add surcharge fields to payment create API calls

## [1.0.33] - 2025-02-24
- Raised timeout of curl client to 30 seconds

## [1.0.32] - 2025-01-15
- Integrated Oauth2 client fully into SDK

## [1.0.31] - 2025-01-14
- Added full test of Oauth2 in API client
- Fix user agent being set incorrectly in get requests
- Add support for surcharge

## [1.0.30] - 2024-08-05
- Allow a broader selection of characters in cartinfo account id
- Added direction parameter to transaction and subscription lists
- Added missing enum for Klarna

## [1.0.29] - 2024-03-04
- Added Klarna method

## [1.0.28] - 2023-12-20
- Added user agent to get requests as well

## [1.0.27] - 2023-12-20
- Added custom user agent to header of API calls, and let window platform inherit this value if not otherwise set (#82)

## [1.0.26] - 2023-09-20
- Add transaction creation from subscription flag in payment creation trough API

## [1.0.25] - 2023-02-22
- Updated readme with info about htmlentity escaping field values.
- Added check for negative values in paymentWindow Cart object.
- Allow whitespace and hyphen characters in PostalCode fields in PaymentInfo object.

## [1.0.24] - 2022-08-17
- Added payment service method to API class
- Added typing of certain parameters that are historically less strict, when creating payments

## [1.0.23] - 2022-07-25
- Added PayPal method support (#72)
- Added sending cart data support (#72)

## [1.0.22] - 2022-05-30
- Introduced payment creation (#60)
- Fixed OAUTH2 codeverifier not being set (#65)
- Added helper functions for currency/method validation (#64, #70)
- Ensure HMAC value being set in payment window (#66)
- Added better handling of unexpected API errors (#67)

## [1.0.21] - 2022-03-31
- Introduce Currency classes (#59, #62, #63)
- Fixed broken PHP doc (#61)
- Fixed potential PHP 8.0 problem  (#58)

## [1.0.20] - 2021-12-07
- Added Swish

## [1.0.19] - 2021-05-05
- Added Vipps

## [1.0.18] - 2021-03-23
- Fixed bug with no links being available when getting transactions
- Added Apple pay and Google pay as methods available for payment window.
- Added testmode flag to transactions and subscriptions.

## [1.0.17] - 2021-03-02
- Added expiration field to payment window.
- Throw an ApiException with a meaningful error message when response json body fails to decode.

## [1.0.16] - 2021-01-28
- When no redirect_uri value is sent to OnPayAPI, add an empty value to the option.

## [1.0.15] - 2021-01-27
- Removed void typehinting on paymentwindow setPlatform method

## [1.0.14] - 2021-01-26
- Added platform field to payment window.

## [1.0.13] - 2021-01-20
- Added static token object for use with static API tokens from OnPay.

## [1.0.12] - 2021-01-12
- Added Anyday Split as available method in payment window.

## [1.0.11] - 2020-12-03
- Introduced onpay_website field for payment window.
- Added exception message for invalid OAUTH2 token.

## [1.0.10] - 2020-10-21
- Handle if oauth2 token format is completely broken.
- Implementing new flag for signaling a subscription should create a transaction.

## [1.0.9] - 2020-10-05
- Fixed bug with the latest version of OAuth2 client library.

## [1.0.8] - 2020-09-30
- Added address fields to CardholderData class.
- Removed amount as required field in PaymentWindow class.
- Added ability to disable delivery with different reasons in PaymentWindow class. Specifically for use with MobilePay Checkout.
- Fixed broken dependencies.

## [1.0.7] - 2020-04-28
- Fixed cardholderData being set to hasCardholderData instead of cardholderData on detailedTransaction (PR #28)

## [1.0.6] - 2020-04-01
- Implemented the PaymentInfo object (PR #22)
- Updated SDK to correspond correctly with API (PR #23)
- Properly fetch requests and responses to/from API for logging purposes (PR #23)
- Check that gateway_id is numeric value when constructing OnPayAPI. (Issue #16) (PR #23)

## [1.0.5] - 2020-02-26
- Set last HTTP response/request methods less strict with param values. Sets empty value if supplied with invalid value.

## [1.0.4] - 2020-02-25
- Removed unused includes in OnPayAPI class (PR #20)
- Handle TokenException thrown by fkooman repo (PR #20)
- Added methods for getting latest http request and responses (PR #20)

## [1.0.3] - 2019-10-18
- Fixed transaction and subscription lists API paths
- Fixed bug with HMAC calculations (PR #19)
- Implement MobilePay checkout (PR #18)

## [1.0.2] - 2019-09-19
- Changed OAUTH2 client implementation (PR #15)

## [1.0.1] - 2019-09-13
- Fixed bug with forcing 3D-Secure (PR #14)

## [1.0.0] - 2019-05-28
- First stable release
