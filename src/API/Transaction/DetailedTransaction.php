<?php

declare(strict_types=1);

namespace OnPay\API\Transaction;

use OnPay\API\Util\DataReader;

final class DetailedTransaction extends SimpleTransaction {

    /**
     * @internal Shall not be used outside the library
     * DetailedTransaction constructor.
     * @param array $data
     */
    public function __construct(array $data)
    {
        parent::__construct($data);

        $this->fee = DataReader::intOrNull($data, 'fee');
        $this->expiryYear = DataReader::intOrNull($data, 'expiry_year');
        $this->expiryMonth = DataReader::intOrNull($data, 'expiry_month');
        $this->cardCountry = DataReader::stringOrNull($data, 'card_country');
        $this->cardBin = DataReader::stringOrNull($data, 'card_bin');
        $this->cardMask = DataReader::stringOrNull($data, 'card_mask');
        $this->ip = DataReader::stringOrNull($data, 'ip');
        $this->ipCountry = DataReader::stringOrNull($data, 'ip_country');

        $this->hasCardholderData = DataReader::boolOr($data, 'has_cardholder_data', false);

        $cardholderData = DataReader::arrayOrNull($data, 'cardholder_data');
        if (null !== $cardholderData) {
            $this->cardholderData = new CardholderData($cardholderData);
        }

        $history = DataReader::arrayOr($data, 'history');
        foreach (array_keys($history) as $key) {
            $this->history[] = new TransactionHistory(is_array($history[$key]) ? $history[$key] : []);
        }

        $this->subscriptionNumber = DataReader::intOrNull($data, 'subscription_number');
        $this->subscriptionUuid = DataReader::stringOrNull($data, 'subscription_uuid');
    }

    /**
     * ISO 3166-1 numeric code, zero-padded to three characters ("208", "004").
     */
    public ?string $cardCountry = null;

    public ?string $cardBin = null;

    public ?string $cardMask = null;

    public ?int $expiryMonth = null;

    public ?int $expiryYear = null;

    public ?string $ip = null;

    /**
     * Zero-padded ISO 3166-1 numeric code, like {@see self::$cardCountry}.
     */
    public ?string $ipCountry = null;

    public bool $hasCardholderData = false;

    public ?CardholderData $cardholderData = null;

    /**
     * @var TransactionHistory[]
     */
    public array $history = [];

    /**
     * Null unless the transaction is merchant-initiated.
     */
    public ?int $subscriptionNumber = null;

    /**
     * Null unless the transaction is merchant-initiated.
     */
    public ?string $subscriptionUuid = null;

    /**
     * Surcharge in minor units; null when the endpoint did not report it. A zero surcharge
     * is reported as 0, so null never means "no surcharge".
     */
    public ?int $fee = null;

}
