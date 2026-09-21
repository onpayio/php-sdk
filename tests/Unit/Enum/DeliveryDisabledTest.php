<?php

namespace Tests\Unit\Enum;

use OnPay\API\Enum\DeliveryDisabled;
use OnPay\API\PaymentWindow;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Pins the {@see DeliveryDisabled} enum as the single source of the delivery_disabled
 * identifiers, and the deprecated {@see PaymentWindow} constants as unchanged aliases of it.
 */
class DeliveryDisabledTest extends TestCase
{
    /**
     * Case => wire value, deprecated PaymentWindow constant.
     *
     * @return array<string, array{DeliveryDisabled, string, string}>
     */
    public static function reasonProvider(): array
    {
        return [
            'no-reason' => [DeliveryDisabled::NO_REASON, 'no-reason', PaymentWindow::DELIVERY_DISABLED_NO_REASON],
            'not-physical' => [
                DeliveryDisabled::NOT_PHYSICAL,
                'not-physical',
                PaymentWindow::DELIVERY_DISABLED_NOT_PHYSICAL,
            ],
            'store-pick-up' => [
                DeliveryDisabled::STORE_PICK_UP,
                'store-pick-up',
                PaymentWindow::DELIVERY_DISABLED_STORE_PICK_UP,
            ],
            'parcel-shop-selected' => [
                DeliveryDisabled::PARCEL_SHOP_SELECTED,
                'parcel-shop-selected',
                PaymentWindow::DELIVERY_DISABLED_PARCEL_SHOP_SELECTED,
            ],
            'parcel-shop-auto' => [
                DeliveryDisabled::PARCEL_SHOP_AUTO,
                'parcel-shop-auto',
                PaymentWindow::DELIVERY_DISABLED_PARCEL_SHOP_AUTO,
            ],
        ];
    }

    #[DataProvider('reasonProvider')]
    public function testCaseValueIsTheWireIdentifier(
        DeliveryDisabled $reason,
        string $wireValue,
        string $paymentWindowConstant,
    ): void {
        $this->assertSame($wireValue, $reason->value);
        $this->assertSame($reason, DeliveryDisabled::from($wireValue));
    }

    #[DataProvider('reasonProvider')]
    public function testDeprecatedAliasResolvesToTheEnumValue(
        DeliveryDisabled $reason,
        string $wireValue,
        string $paymentWindowConstant,
    ): void {
        $this->assertSame($reason->value, $paymentWindowConstant);
    }

    public function testEnumCoversExactlyTheKnownReasons(): void
    {
        $values = array_map(static fn (DeliveryDisabled $r) => $r->value, DeliveryDisabled::cases());

        $this->assertSame([
            'no-reason',
            'not-physical',
            'store-pick-up',
            'parcel-shop-selected',
            'parcel-shop-auto',
        ], $values);
    }
}
