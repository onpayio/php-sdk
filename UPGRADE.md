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

_No changes recorded yet._

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
