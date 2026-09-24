# OnPay PHP SDK

[![Latest Stable Version](https://poser.pugx.org/onpayio/php-sdk/v/stable)](https://packagist.org/packages/onpayio/php-sdk)
[![Total Downloads](https://poser.pugx.org/onpayio/php-sdk/downloads)](https://packagist.org/packages/onpayio/php-sdk)
[![License](https://poser.pugx.org/onpayio/php-sdk/license)](https://packagist.org/packages/onpayio/php-sdk)

PHP SDK for the [OnPay](https://onpay.io) payment platform.

- API reference: https://onpay.io/docs/technical/api_v1.html
- Payment window reference: https://onpay.io/docs/technical/paymentwindow_v3.html
- Upgrading from 1.x: [UPGRADE.md](UPGRADE.md)

## Requirements

- PHP 8.2 or later.
- A [PSR-18](https://www.php-fig.org/psr/psr-18/) HTTP client with
  [PSR-17](https://www.php-fig.org/psr/psr-17/) factories, for example Guzzle. The SDK does
  not ship one. A common client such as Guzzle or Symfony's is discovered automatically;
  anything else you pass to the constructor (see
  [Using your own HTTP client](#using-your-own-http-client)).

## Installation

```bash
composer require onpayio/php-sdk guzzlehttp/guzzle
```

Replace `guzzlehttp/guzzle` with the PSR-18 client you use. Composer may ask whether to
allow the `php-http/discovery` plugin; either answer works, discovery needs no plugin at
runtime.

## Getting started

### 1. Store the access token

The SDK hands you the OAuth token as a string and asks for it back on every request.
Implement `OnPay\TokenStorageInterface` to keep it somewhere durable and private: the
token gives full API access, so treat it like a database password.

```php
final class FileTokenStorage implements \OnPay\TokenStorageInterface
{
    public function __construct(private string $file) {}

    public function getToken(): ?string
    {
        $token = is_file($this->file) ? file_get_contents($this->file) : false;

        return $token === false ? null : $token;
    }

    public function saveToken(string $token): void
    {
        file_put_contents($this->file, $token);
    }
}
```

### 2. Store the authorization state

The OAuth flow spans two requests: one that redirects the merchant to OnPay and one
that receives the authorization code. Implement `OnPay\AuthStateStorageInterface` to
carry the CSRF `state` and PKCE verifier between them, scoped to the current visitor.
A PHP session is the simplest place:

```php
final class SessionAuthStateStorage implements \OnPay\AuthStateStorageInterface
{
    public function saveState(string $state): void { $_SESSION['onpay_state'] = $state; }
    public function getState(): ?string { return $_SESSION['onpay_state'] ?? null; }
    public function saveCodeVerifier(string $codeVerifier): void { $_SESSION['onpay_verifier'] = $codeVerifier; }
    public function getCodeVerifier(): ?string { return $_SESSION['onpay_verifier'] ?? null; }
    public function clear(): void { unset($_SESSION['onpay_state'], $_SESSION['onpay_verifier']); }
}
```

### 3. Authorize

```php
session_start();

$api = new \OnPay\OnPayAPI(
    new FileTokenStorage(__DIR__ . '/../var/onpay-token.json'),
    [
        'client_id'    => 'example.com',                         // the domain your integration runs on
        'redirect_uri' => 'https://example.com/onpay/callback',  // where OnPay sends the merchant back
        'gateway_id'   => 'YourGatewayId',
        'platform'     => 'my-shop-plugin/1.2.3',                // optional: identifies your integration to OnPay
    ],
    new SessionAuthStateStorage(),
);

// Step 1: send the merchant to OnPay to approve the integration.
header('Location: ' . $api->authorize());

// Step 2: on the redirect_uri, exchange the returned code for a token.
$api->finishAuthorize($_GET['code'], $_GET['state']);
```

After that the token is refreshed and re-saved automatically. `isAuthorized()` makes a
`ping` request to check that the stored token still works.

A static API token created in the OnPay management panel skips the flow entirely:

```php
$api = new \OnPay\OnPayAPI(new \OnPay\StaticToken('YourStaticToken'), [
    'client_id' => 'example.com',
]);
```

### 4. Create a payment

Describe the payment with a `PaymentWindow`, create it through the API and send the
shopper to the returned payment window link. Amounts are in minor units.

```php
use OnPay\API\Enum\PaymentMethod;
use OnPay\API\PaymentWindow;
use OnPay\API\PaymentWindow\PaymentInfo;

$window = new PaymentWindow();
$window->setCurrency('DKK');
$window->setAmount('12345');
$window->setReference('order-1001');            // unique per payment, e.g. the order number
$window->setWebsite('https://example.com');
$window->setAcceptUrl('https://example.com/onpay/accept');
$window->setDeclineUrl('https://example.com/onpay/decline');
$window->setCallbackUrl('https://example.com/onpay/callback');
$window->setMethod(PaymentMethod::CARD);
$window->setLanguage('en');
$window->setTestModeEnabled(true);

// Required when you have it (SCA and card-scheme rules): what you already know about the
// shopper, for 3-D Secure. Do not collect extra data just for this.
$info = new PaymentInfo();
$info->setName('Jane Doe');
$info->setEmail('jane@example.com');
$info->setBillingAddressCountry('208');
$window->setInfo($info);

$payment = $api->payment()->createNewPayment($window);

header('Location: ' . $payment->getPaymentWindowLink());
```

Every field the API accepts has a setter on `PaymentWindow` and `PaymentInfo`; the
[API reference](https://onpay.io/docs/technical/api_v1.html#create-a-new-payment-request)
describes them. Some payment methods need a cart. Its total, items plus shipping and
handling minus discount, must equal the payment amount:

```php
use OnPay\API\PaymentWindow\Cart;
use OnPay\API\PaymentWindow\CartItem;

$cart = new Cart();
$cart->addItem(new CartItem('T-shirt', 9900, 1, 1980, sku: 'TS-001'));  // name, price, quantity, tax
$cart->setShipping(2445, 489);                                           // price, tax
$window->setCart($cart);                                                 // 9900 + 2445 = 12345
```

To store the card for later charges, create a subscription (see
[Stored cards](#stored-cards-cof)) instead of a one-off payment. It should charge its first
transaction as part of the signup, so the shopper authenticates the real amount and liability
shifts to the issuer. Create one without a transaction only for a trial period or when
replacing a stored card.

```php
$window->setType('subscription');
$window->setSubscriptionWithTransaction(true);
```

### 5. Verify the result

OnPay signs the `onpay_*` parameters it appends to the accept URL and sends to the callback
URL (a GET request). Check the signature with your payment window secret before trusting
them. Declines sent to the decline URL are not signed.

```php
$window = new \OnPay\API\PaymentWindow();
$window->setSecret('YourWindowSecret');

if (!$window->validatePayment($_GET)) {
    http_response_code(400);
    exit;
}
```

## Transactions

```php
$transactions = $api->transaction();

$page = $transactions->getTransactions(page: 1, pageSize: 50, status: 'active');
foreach ($page->transactions as $transaction) {
    echo $transaction->transactionNumber, ' ', $transaction->status, PHP_EOL;
}
$page->pagination->totalPages;

$transaction = $transactions->getTransaction('00000000-0000-0000-0000-000000000000');

// Capture everything that was authorised.
$transactions->captureTransaction($transaction->uuid);

// Partial capture or refund: state the total you want charged or refunded after the call,
// not the amount to add. Repeating the call is then harmless, and an earlier partial
// capture is accounted for.
$transactions->captureTransaction($transaction->uuid, postActionChargeAmount: 5000);
$transactions->refundTransaction($transaction->uuid, postActionRefundAmount: 2500);

$transactions->cancelTransaction($transaction->uuid);
```

## Stored cards (COF)

OnPay stores a card as a *subscription*, the card on file (COF) of the card-scheme rules,
and that is the name the API and these methods use. Charging it later, without the shopper
present, is a merchant-initiated transaction (MIT).

```php
$subscriptions = $api->subscription();

$page = $subscriptions->getSubscriptions(status: 'active');
$subscription = $subscriptions->getSubscription('00000000-0000-0000-0000-000000000000');

// MIT: charge the stored card; returns the new transaction.
$transaction = $subscriptions->createTransactionFromSubscription($subscription->uuid, 9900, 'order-1002');

$subscriptions->cancelSubscription($subscription->uuid);
```

## Gateway

Configuration-time lookups. Cache the results; do not call these while processing a payment.

```php
$api->gateway()->getInformation();                       // gateway id
$api->gateway()->getPaymentWindowDesigns();              // designs available to setDesign()
$api->gateway()->getPaymentWindowIntegrationSettings();  // payment window secret
```

## Errors

Every error the SDK raises extends `OnPay\API\Exception\OnPayException`:

| Exception | Meaning |
| --- | --- |
| `TokenException` | No usable token, or OnPay rejected it. Re-authorize. |
| `ConnectionException` | The HTTP request never got a response. |
| `ApiException` | OnPay answered with an error, or with a response the SDK could not read. |
| `MissingDataException`, `InvalidFormatException`, `InvalidCartException` | The `PaymentWindow` you built is incomplete or invalid. |

```php
try {
    $api->transaction()->captureTransaction($uuid);
} catch (\OnPay\API\Exception\TokenException $e) {
    // send the merchant through authorize() again
} catch (\OnPay\API\Exception\OnPayException $e) {
    $request  = $api->getLastHttpRequest();   // what was sent
    $response = $api->getLastHttpResponse();  // what came back
}
```

Invalid constructor options throw a plain `\InvalidArgumentException`.

## Logging

Failed requests are logged at warning (4xx) or error (5xx, transport failure), successful
ones at debug. Tokens and secrets are redacted first. Pass any PSR-3 logger; without one,
warnings and errors go to PHP's `error_log()`.

```php
$api = new \OnPay\OnPayAPI($tokenStorage, $options, $authStateStorage, logger: $logger);
```

Pass a `Psr\Log\NullLogger` to silence the SDK.

## Using your own HTTP client

Any PSR-18 client works. When you inject one, timeouts, proxies and TLS settings are
whatever you configured on it. When the SDK discovers Guzzle itself it creates the client
with a 30-second timeout and a 5-second connect timeout; any other discovered client runs
with its own defaults.

```php
$api = new \OnPay\OnPayAPI(
    $tokenStorage,
    $options,
    $authStateStorage,
    httpClient: $client,               // Psr\Http\Client\ClientInterface
    requestFactory: $requestFactory,   // Psr\Http\Message\RequestFactoryInterface
    streamFactory: $streamFactory,     // Psr\Http\Message\StreamFactoryInterface
);
```

Factories you leave out are discovered from the installed packages.

## Payment window as an HTML form

The payment window can also be opened by posting a signed form directly from the browser,
without an API call. It needs the gateway id and payment window secret from the OnPay
management panel, plus an accept URL. Prefer [creating the payment through the API](#4-create-a-payment)
when you can.

```php
$window = new \OnPay\API\PaymentWindow();
$window->setGatewayId('YourGatewayId');
$window->setSecret('YourWindowSecret');
$window->setCurrency('DKK');
$window->setAmount('12345');
$window->setReference('order-1001');
$window->setAcceptUrl('https://example.com/onpay/accept');
$window->setWebsite('https://example.com');
?>
<?php if ($window->isValid()): ?>
<form method="post" action="<?= $window->getActionUrl() ?>" accept-charset="UTF-8">
    <?php foreach ($window->getFormFields() as $name => $value): ?>
    <input type="hidden" name="<?= $name ?>" value="<?= htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML401, 'UTF-8') ?>">
    <?php endforeach ?>
    <button type="submit">Pay</button>
</form>
<?php endif ?>
```

The result is verified exactly as in [Verify the result](#5-verify-the-result).
