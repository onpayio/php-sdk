# Upgrading from 1.x to 2.0

This document is a running guide to upgrading the OnPay.io PHP SDK from the `1.x`
series to `2.0`. It is maintained throughout the `2.0` development cycle: every
change that breaks backwards compatibility is recorded here as it lands, so that
by the time `2.0` is released this file is the complete, authoritative migration
reference.

If you are upgrading, read this document top to bottom and apply each change that
affects code you use.

## What 2.0 is

`2.0` is the next major version of the SDK. Following [Semantic
Versioning](https://semver.org/spec/v2.0.0.html), it is the release where we
introduce backwards-incompatible ("BC") changes that could not be made within the
`1.x` line — modernising the codebase, tightening types, and dropping support for
end-of-life PHP versions.

## Requirements

- **PHP 8.2 or later.** Support for PHP 7.4, 8.0, and 8.1 has been dropped.

Update your environment (and your own `composer.json` constraints, if you pin
them) before upgrading.

## Installation

Once `2.0` is released:

```bash
composer require onpayio/php-sdk:^2.0
```

## Backwards-incompatible changes

> Changes are appended to this section as they are made during the `2.0` cycle.
> Each entry states what changed, why, and what you need to do to migrate.

### An installed PSR-18 HTTP client is now used by default

**What changed:** `OnPayAPI` no longer always uses its own bundled cURL client.
When no client is passed to the constructor, the SDK auto-discovers an installed
PSR-18 client (via `php-http/discovery`) and uses it for all API and OAuth
traffic, only falling back to the bundled cURL client when none is installed. So
if your project already has a PSR-18 client available (e.g. Guzzle), OnPay traffic
will now run through it after upgrading, without any code change on your part.

**Why:** it makes the HTTP boundary pluggable and testable, and lets you control
transport (timeouts, proxies, TLS, middleware) instead of being locked to the
vendored cURL client.

**Impact:** the requests are unchanged, but they run over the discovered client's
transport — its timeouts, proxies, retries, TLS settings and middleware apply to
OnPay traffic and may behave differently from the SDK's cURL defaults.

**How to migrate:** to keep control over which client is used, pass one explicitly
(with PSR-17 factories) rather than relying on discovery:

```php
$factory = new \GuzzleHttp\Psr7\HttpFactory(); // implements both PSR-17 factories
$api = new OnPay\OnPayAPI($tokenStorage, $options, new \GuzzleHttp\Client(), $factory, $factory);
```

The existing two-argument constructor still works; `getLastHttpRequest()` /
`getLastHttpResponse()` keep working on every path.

<!--
Template for a new entry:

### <Short title of the change>

**What changed:** …

**Why:** …

**How to migrate:**

Before:

```php
// old usage
```

After:

```php
// new usage
```
-->
