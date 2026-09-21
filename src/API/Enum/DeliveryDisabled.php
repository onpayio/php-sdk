<?php

declare(strict_types=1);

namespace OnPay\API\Enum;

/**
 * The reasons the payment window can be told that delivery-address collection is disabled.
 *
 * Each case's value is the identifier sent to the gateway as the payment window's
 * `delivery_disabled` field. This enum is the single source of truth for those identifiers:
 * {@see \OnPay\API\PaymentWindow}'s `DELIVERY_DISABLED_*` constants are defined in terms of it.
 */
enum DeliveryDisabled: string
{
    case NO_REASON = 'no-reason';
    case NOT_PHYSICAL = 'not-physical';
    case STORE_PICK_UP = 'store-pick-up';
    case PARCEL_SHOP_SELECTED = 'parcel-shop-selected';
    case PARCEL_SHOP_AUTO = 'parcel-shop-auto';
}
