<?php

declare(strict_types=1);

namespace OnPay\API\Exception;

/**
 * Common base for every runtime error the SDK raises: transport failures, token-endpoint
 * failures, API error responses and request/response validation. Consumers can catch this
 * single type to handle any of them, or catch a specific subclass
 * (e.g. {@see TokenException}, {@see ConnectionException}, {@see ApiException}) for
 * finer control.
 *
 * Misconfiguration and misuse (invalid constructor options, unsupported operations) are
 * programmer errors and still throw SPL `\InvalidArgumentException` / `\LogicException`;
 * they are deliberately not part of this hierarchy.
 *
 * All exceptions in {@see \OnPay\API\Exception} extend this class; it in turn extends
 * {@see \Exception}, so existing `catch (\Exception $e)` and per-subclass catch sites
 * keep working unchanged. The class is abstract: a thrown SDK exception is always one of
 * the concrete subclasses.
 */
abstract class OnPayException extends \Exception
{

}
