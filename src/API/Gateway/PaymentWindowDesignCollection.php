<?php

declare(strict_types=1);

namespace OnPay\API\Gateway;


use OnPay\API\Gateway\SimplePaymentWindowDesign;

final class PaymentWindowDesignCollection
{
    /**
     * @var SimplePaymentWindowDesign[]
     */
    public array $paymentWindowDesigns = [];
}

