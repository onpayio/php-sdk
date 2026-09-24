<?php

namespace Tests\Unit\Enum;

use OnPay\API\Enum\PaymentMethod;
use OnPay\API\PaymentWindow;
use OnPay\API\Util\PaymentMethods\Enums\Methods;
use OnPay\API\Util\PaymentMethods\Methods\Anyday;
use OnPay\API\Util\PaymentMethods\Methods\ApplePay;
use OnPay\API\Util\PaymentMethods\Methods\Card;
use OnPay\API\Util\PaymentMethods\Methods\GooglePay;
use OnPay\API\Util\PaymentMethods\Methods\Klarna;
use OnPay\API\Util\PaymentMethods\Methods\MobilePay;
use OnPay\API\Util\PaymentMethods\Methods\MobilePayCheckout;
use OnPay\API\Util\PaymentMethods\Methods\PaymentMethodInterface;
use OnPay\API\Util\PaymentMethods\Methods\PayPal;
use OnPay\API\Util\PaymentMethods\Methods\Swish;
use OnPay\API\Util\PaymentMethods\Methods\ViaBill;
use OnPay\API\Util\PaymentMethods\Methods\Vipps;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Pins the {@see PaymentMethod} enum as the single source of truth for method identifiers.
 *
 * The wire values are what the gateway sees, so each case's value is asserted as a literal,
 * and the two deprecated constant sources kept for backwards compatibility
 * ({@see PaymentWindow}'s `METHOD_*` and {@see Methods}) are asserted to still resolve to
 * exactly the same strings.
 */
class PaymentMethodTest extends TestCase
{
    /**
     * Case name => wire value, deprecated PaymentWindow constant, deprecated Methods constant.
     *
     * @return array<string, array{PaymentMethod, string, string, string}>
     */
    public static function methodProvider(): array
    {
        return [
            'anyday' => [PaymentMethod::ANYDAY, 'anyday', PaymentWindow::METHOD_ANYDAY, Methods::ANYDAY],
            'applepay' => [PaymentMethod::APPLE_PAY, 'applepay', PaymentWindow::METHOD_APPLEPAY, Methods::APPLE_PAY],
            'card' => [PaymentMethod::CARD, 'card', PaymentWindow::METHOD_CARD, Methods::CARD],
            'googlepay' => [PaymentMethod::GOOGLE_PAY, 'googlepay', PaymentWindow::METHOD_GOOGLEPAY, Methods::GOOGLE_PAY],
            'klarna' => [PaymentMethod::KLARNA, 'klarna', PaymentWindow::METHOD_KLARNA, Methods::KLARNA],
            'mobilepay' => [PaymentMethod::MOBILEPAY, 'mobilepay', PaymentWindow::METHOD_MOBILEPAY, Methods::MOBILEPAY],
            'mobilepay_checkout' => [
                PaymentMethod::MOBILEPAY_CHECKOUT,
                'mobilepay_checkout',
                PaymentWindow::METHOD_MOBILEPAY_CHECKOUT,
                Methods::MOBILEPAY_CHECKOUT,
            ],
            'paypal' => [PaymentMethod::PAYPAL, 'paypal', PaymentWindow::METHOD_PAYPAL, Methods::PAYPAL],
            'swish' => [PaymentMethod::SWISH, 'swish', PaymentWindow::METHOD_SWISH, Methods::SWISH],
            'viabill' => [PaymentMethod::VIABILL, 'viabill', PaymentWindow::METHOD_VIABILL, Methods::VIABILL],
            'vipps' => [PaymentMethod::VIPPS, 'vipps', PaymentWindow::METHOD_VIPPS, Methods::VIPPS],
        ];
    }

    #[DataProvider('methodProvider')]
    public function testCaseValueIsTheWireIdentifier(
        PaymentMethod $method,
        string $wireValue,
        string $paymentWindowConstant,
        string $methodsConstant,
    ): void {
        $this->assertSame($wireValue, $method->value);
        $this->assertSame($method, PaymentMethod::from($wireValue));
    }

    /**
     * The deprecated aliases are defined in terms of the enum, so they must not drift.
     */
    #[DataProvider('methodProvider')]
    public function testDeprecatedAliasesResolveToTheEnumValue(
        PaymentMethod $method,
        string $wireValue,
        string $paymentWindowConstant,
        string $methodsConstant,
    ): void {
        $this->assertSame($method->value, $paymentWindowConstant);
        $this->assertSame($method->value, $methodsConstant);
    }

    public function testEnumCoversExactlyTheKnownMethods(): void
    {
        $values = array_map(static fn (PaymentMethod $m) => $m->value, PaymentMethod::cases());

        $this->assertSame([
            'anyday',
            'applepay',
            'card',
            'googlepay',
            'klarna',
            'mobilepay',
            'mobilepay_checkout',
            'paypal',
            'swish',
            'viabill',
            'vipps',
        ], $values);
    }

    public function testUnknownIdentifierIsNotAKnownCase(): void
    {
        $this->assertNull(PaymentMethod::tryFrom('some-future-method'));
    }

    /**
     * @return array<string, array{PaymentMethodInterface, PaymentMethod}>
     */
    public static function methodClassProvider(): array
    {
        return [
            Anyday::class => [new Anyday(), PaymentMethod::ANYDAY],
            ApplePay::class => [new ApplePay(), PaymentMethod::APPLE_PAY],
            Card::class => [new Card(), PaymentMethod::CARD],
            GooglePay::class => [new GooglePay(), PaymentMethod::GOOGLE_PAY],
            Klarna::class => [new Klarna(), PaymentMethod::KLARNA],
            MobilePay::class => [new MobilePay(), PaymentMethod::MOBILEPAY],
            MobilePayCheckout::class => [new MobilePayCheckout(), PaymentMethod::MOBILEPAY_CHECKOUT],
            PayPal::class => [new PayPal(), PaymentMethod::PAYPAL],
            Swish::class => [new Swish(), PaymentMethod::SWISH],
            ViaBill::class => [new ViaBill(), PaymentMethod::VIABILL],
            Vipps::class => [new Vipps(), PaymentMethod::VIPPS],
        ];
    }

    /**
     * Every method class reports itself through the enum, and getName() stays the raw string.
     */
    #[DataProvider('methodClassProvider')]
    public function testMethodClassesAreEnumBacked(PaymentMethodInterface $method, PaymentMethod $expected): void
    {
        $this->assertSame($expected, $method->getMethod());
        $this->assertSame($expected->value, $method->getName());
        $this->assertSame($expected->value, $method::METHOD_NAME);
    }
}
