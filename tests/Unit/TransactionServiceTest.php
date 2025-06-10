<?php

use OnPay\API\TransactionService;
use OnPay\API\Exception\ApiException;
use PHPUnit\Framework\TestCase;

class TransactionServiceTest extends TestCase
{
    private $apiMock;
    private $service;

    protected function setUp(): void
    {
        $this->apiMock = $this->createMock(\OnPay\OnPayAPI::class);
        $this->service = new TransactionService($this->apiMock);
    }

    public function testGetTransactionThrowsOnEmptyIdentifier()
    {
        $this->expectException(ApiException::class);
        $this->service->getTransaction('');
    }

    public function testCaptureTransactionThrowsOnEmptyTransactionNumber()
    {
        $this->expectException(ApiException::class);
        $this->service->captureTransaction('');
    }

    public function testCancelTransactionThrowsOnEmptyTransactionNumber()
    {
        $this->expectException(ApiException::class);
        $this->service->cancelTransaction('');
    }

    public function testRefundTransactionThrowsOnEmptyTransactionNumber()
    {
        $this->expectException(ApiException::class);
        $this->service->refundTransaction('');
    }

    public function testGetTransactionReturnsDetailedTransactionOnValidIdentifier()
    {
        $uuid = '123e4567-e89b-12d3-a456-426614174000';
        $mockResult = [
            'data' => ['uuid' => $uuid],
            'links' => ['self' => '/transaction/' . $uuid]
        ];

        $this->apiMock->method('get')
            ->with('transaction/' . $uuid)
            ->willReturn($mockResult);

        $service = new TransactionService($this->apiMock);
        $result = $service->getTransaction($uuid);

        $this->assertInstanceOf(\OnPay\API\Transaction\DetailedTransaction::class, $result);
        $this->assertEquals($uuid, $result->uuid);
    }

    public function testCaptureTransactionReturnsDetailedTransactionOnValidNumber()
    {
        $uuid = '123e4567-e89b-12d3-a456-426614174000';
        $mockResult = [
            'data' => ['uuid' => $uuid],
            'links' => ['self' => '/transaction/' . $uuid]
        ];

        $this->apiMock->method('post')
            ->with('transaction/' . $uuid . '/capture', $this->anything())
            ->willReturn($mockResult);

        $service = new TransactionService($this->apiMock);
        $result = $service->captureTransaction($uuid);

        $this->assertInstanceOf(\OnPay\API\Transaction\DetailedTransaction::class, $result);
        $this->assertEquals($uuid, $result->uuid);
    }
}