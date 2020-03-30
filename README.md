# OnPay.io PHP SDK

[![Latest Stable Version](https://poser.pugx.org/onpayio/php-sdk/v/stable)](https://packagist.org/packages/onpayio/php-sdk)
[![Total Downloads](https://poser.pugx.org/onpayio/php-sdk/downloads)](https://packagist.org/packages/onpayio/php-sdk)
[![License](https://poser.pugx.org/onpayio/php-sdk/license)](https://packagist.org/packages/onpayio/php-sdk)

A PHP-SDK for developing against the OnPay.io platform.
API documentation at: https://manage.onpay.io/docs/api_v1.html 

## Requirements

PHP 5.6 and later.

## Composer

You can install the SDK via [Composer](https://getcomposer.org/). Run the following command:
```bash
composer require onpayio/php-sdk
```

## Getting started



### Creating a payment window V3

A simple example of how to create a payment window v3.

Read more about the fields here: https://manage.onpay.io/docs/paymentwindow_v3.html

```php 
<?php 
require_once 'vendor/autoload.php';

$paymentWindow = new \OnPay\API\PaymentWindow();
$paymentWindow->setGatewayId("YourGatewayId");
$paymentWindow->setSecret("YourSecret");
$paymentWindow->setCurrency("DKK");
$paymentWindow->setAmount("123400");
// Reference must be unique (eg. invoice number)
$paymentWindow->setReference("UniqieReferenceId");
$paymentWindow->setAcceptUrl("https://example.com/payment?success=1");
$paymentWindow->setDeclineUrl("https://example.com/payment?success=0");
$paymentWindow->setType("payment");
$paymentWindow->setDesign("DesignName");
// Force 3D secure
$paymentWindow->setSecureEnabled(true);
// Set payment method to be card
$paymentWindow->setMethod(\OnPay\API\PaymentWindow::METHOD_CARD);
// Enable testmode
$paymentWindow->setTestMode(true);
$paymentWindow->setLanguage("en");

// Add additional info
$paymentInfo = new \OnPay\API\PaymentWindow\PaymentInfo();
$paymentInfo->setName('Test Pærsån');
$paymentInfo->setEmail('emil@example.com');
// And so on, a lot more fields should be set if data available for it

$paymentWindow->setInfo($paymentInfo);

?>

<?php if($paymentWindow->isValid()) { ?>
<form method="post" action="<?php echo $paymentWindow->getActionUrl(); ?>" accept-charset="UTF-8">
    <?php
        foreach ($paymentWindow->getFormFields() as $key => $value) { ?>
            <input type="hidden" name="<?php echo $key;?>" value="<?php echo $value;?>">
    <?php } ?>
    <input type="submit" value="Make payment">
</form>

<?php } else { ?>
    <h1>Payment window is not configured correct</h1>
<?php } ?>

```

Verifying a payment from the acceptment page can easily be done as following: 

```php
$payment = new \OnPay\API\PaymentWindow();
$payment->setSecret('YourSecret');

if($payment->validatePayment($_GET)) {
    echo "Payment was successfull";
} else {
    echo "There was an error with the payment";
}

```



### Using the API 

A simple usage example looks like:

```php
<?php

require_once __DIR__ . '/vendor/autoload.php';

class TokenStorage implements \OnPay\TokenStorageInterface {
    protected $token;
    protected $fileName;

    public function __construct($filename) {
        $this->fileName = $filename;
        if (file_exists($filename)) {
            $this->token = file_get_contents($this->fileName);
        }
    }

    public function getToken() {
        return $this->token;
    }

    public function saveToken($token) {
        $this->token = $token;
        file_put_contents($this->fileName, $token);
    }
}

// TODO: It is extremely important that the .token.bin file is not accessible from the internet.
// It gives complete API access to anyone that gets a hold of it, treat it like a database password!
$tokenStorage = new TokenStorage(__DIR__ . '/.token.bin');

$onPayAPI = new \OnPay\OnPayAPI($tokenStorage, [
    'client_id' => 'example.com', // It is recommended to set it to the domain name the integration resides on
    'redirect_uri' => 'http://localhost/onpay-php-sdk/example2.php?auth',
    'gateway_id' => '1234', // Should be set to the gateway id you are integrating with
]);

// Special handling if we are about to auth against the API
if (isset($_GET['auth'])) {
    if (!isset($_GET['code'])) {
        $authUrl = $onPayAPI->authorize();
        header('Location: ' . $authUrl);
    } else {
        $onPayAPI->finishAuthorize($_GET['code']);
        echo 'Authorized :tada:' . PHP_EOL;
    }
    exit;
}

// Check if we need to authenticate
if (!$onPayAPI->isAuthorized()) {
    echo 'Not authorized for the API! ' . PHP_EOL;
    echo '<a href=?auth>Click here to initiate authorization</a>' . PHP_EOL;
    exit;
}

// Execute API method
var_dump($onPayAPI->ping());

```




## Transactions 

```php 
<?php 
if($onPayAPI->isAuthorized()) {

    // Get list of transactions
    $onPayAPI->transaction()->getTransactions()->transactions;
    // Get the pagination object
    $onPayAPI->transaction()->getTransactions()->pagination;
    
    // Get specific transaction
    $onPayAPI->transaction()->getTransaction("00000000-0000-0000-0000-000000000000");

    // Capture transaction
    $onPayAPI->transaction()->captureTransaction("00000000-0000-0000-0000-000000000000");

    // Cancel transaction 
    $onPayAPI->transaction()->cancelTransaction("00000000-0000-0000-0000-000000000000");

}

```

## Subscriptions

```php
<?php
if($onPayAPI->isAuthorized()) {
    // Get list of subscriptions
    $onPayAPI->subscription()->getSubscriptions()->subscriptions;
    // Get the pagination object
     $onPayAPI->subscription()->getSubscriptions()->pagination;
   
   
    // Get details about a specific subscription
    $onPayAPI->subscription()->getSubscription("00000000-0000-0000-0000-000000000000");

    // Create transaction from subscription
    $onPayAPI->subscription()->createTransactionFromSubscription("00000000-0000-0000-0000-000000000000", 100, "orderId");
}

```
