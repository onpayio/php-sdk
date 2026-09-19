<?php

namespace Tests\Unit\Util;

use OnPay\API\Util\Pagination;
use PHPUnit\Framework\TestCase;

/**
 * Unit coverage for {@see Pagination}, focused on the links parsing branch.
 *
 * The harness collection tests only ever see a single-page response, where the
 * real API sends "links": {} and both nextUrl/previousUrl resolve to null. This
 * pins the OTHER side: a multi-page response whose links.next / links.previous
 * carry real URLs must surface them on the value object.
 */
class PaginationTest extends TestCase
{
    public function testParsesPopulatedNextAndPreviousLinks(): void
    {
        $pagination = new Pagination([
            'total' => 50,
            'total_pages' => 3,
            'links' => [
                'previous' => 'https://api.onpay.io/v1/transaction/?page=1',
                'next' => 'https://api.onpay.io/v1/transaction/?page=3',
            ],
        ]);

        $this->assertSame(50, $pagination->total);
        $this->assertSame(3, $pagination->totalPages);
        $this->assertSame('https://api.onpay.io/v1/transaction/?page=1', $pagination->previousUrl);
        $this->assertSame('https://api.onpay.io/v1/transaction/?page=3', $pagination->nextUrl);
    }

    public function testEmptyLinksObjectYieldsNullUrls(): void
    {
        // Faithful single-page shape from the real API: links is an empty object.
        $pagination = new Pagination([
            'total' => 2,
            'total_pages' => 1,
            'links' => [],
        ]);

        $this->assertSame(2, $pagination->total);
        $this->assertSame(1, $pagination->totalPages);
        $this->assertNull($pagination->nextUrl);
        $this->assertNull($pagination->previousUrl);
    }

    public function testMissingKeysYieldNullDefaults(): void
    {
        $pagination = new Pagination([]);

        $this->assertNull($pagination->total);
        $this->assertNull($pagination->totalPages);
        $this->assertNull($pagination->nextUrl);
        $this->assertNull($pagination->previousUrl);
    }
}
