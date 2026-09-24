<?php

declare(strict_types=1);

namespace Tests\Unit\Exception;

use OnPay\API\Exception\ApiException;
use OnPay\API\Exception\ConnectionException;
use OnPay\API\Exception\InvalidCartException;
use OnPay\API\Exception\InvalidFormatException;
use OnPay\API\Exception\MissingDataException;
use OnPay\API\Exception\OnPayException;
use OnPay\API\Exception\TokenException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Locks the unified exception tree: every SDK exception is an OnPayException, which is
 * itself an \Exception. Consumers rely on being able to catch either the common base or
 * a specific subclass.
 */
class ExceptionHierarchyTest extends TestCase
{
    /**
     * @return array<string, array{class-string}>
     */
    public static function sdkExceptions(): array
    {
        return [
            ApiException::class => [ApiException::class],
            ConnectionException::class => [ConnectionException::class],
            InvalidCartException::class => [InvalidCartException::class],
            InvalidFormatException::class => [InvalidFormatException::class],
            MissingDataException::class => [MissingDataException::class],
            TokenException::class => [TokenException::class],
        ];
    }

    /**
     * @param class-string $exceptionClass
     */
    #[DataProvider('sdkExceptions')]
    public function testEverySdkExceptionExtendsTheCommonBase(string $exceptionClass): void
    {
        self::assertTrue(
            is_subclass_of($exceptionClass, OnPayException::class),
            $exceptionClass . ' must extend ' . OnPayException::class
        );
    }

    public function testTheCommonBaseIsAStandardException(): void
    {
        self::assertTrue(is_subclass_of(OnPayException::class, \Exception::class));
    }

    public function testTheCommonBaseCannotBeThrownDirectly(): void
    {
        self::assertTrue((new \ReflectionClass(OnPayException::class))->isAbstract());
    }

    public function testASubclassIsCatchableAsTheCommonBaseAndAsException(): void
    {
        $thrown = new TokenException('boom');

        try {
            throw $thrown;
        } catch (OnPayException $e) {
            self::assertSame($thrown, $e);
        }

        try {
            throw $thrown;
        } catch (\Exception $e) {
            self::assertSame($thrown, $e);
        }
    }
}
