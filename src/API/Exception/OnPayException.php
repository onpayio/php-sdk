<?php

declare(strict_types=1);

namespace OnPay\API\Exception;

/**
 * Common base for every exception the SDK throws. Consumers can catch this single
 * type to handle any SDK-originated error, or catch a specific subclass
 * (e.g. {@see TokenException}, {@see ConnectionException}, {@see ApiException}) for
 * finer control.
 *
 * All exceptions in {@see \OnPay\API\Exception} extend this class; it in turn extends
 * {@see \Exception}, so existing `catch (\Exception $e)` and per-subclass catch sites
 * keep working unchanged.
 */
class OnPayException extends \Exception
{

}
