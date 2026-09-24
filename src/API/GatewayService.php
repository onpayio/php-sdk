<?php

declare(strict_types=1);

namespace OnPay\API;


use OnPay\API\Gateway\Information;
use OnPay\API\Gateway\PaymentWindowDesignCollection;
use OnPay\API\Gateway\PaymentWindowIntegrationSettings;
use OnPay\API\Gateway\SimplePaymentWindowDesign;
use OnPay\API\Util\DataReader;
use OnPay\Http\ApiClient;

final class GatewayService
{

    private ApiClient $api;

    /**
     * @internal Should never be called outside the library
     * GatewayService constructor.
     * @param ApiClient $apiClient
     */
    public function __construct(ApiClient $apiClient) {
        $this->api = $apiClient;
    }

    /**
     * @return Information
     * @throws Exception\ApiException
     * @throws Exception\ConnectionException
     */
    public function getInformation(): Information {
        $result = $this->api->get('gateway/information');

        $information = new Information(DataReader::arrayOr($result, 'data'));
        return $information;
    }

    /**
     * @return PaymentWindowIntegrationSettings
     * @throws Exception\ApiException
     * @throws Exception\ConnectionException
     */
    public function getPaymentWindowIntegrationSettings(): PaymentWindowIntegrationSettings {
        $result = $this->api->get('gateway/window/v3/integration');

        $settings = new PaymentWindowIntegrationSettings(DataReader::arrayOr($result, 'data'));
        return $settings;
    }

    /**
     * @return PaymentWindowDesignCollection
     * @throws Exception\ApiException
     * @throws Exception\ConnectionException
     */
    public function getPaymentWindowDesigns(): PaymentWindowDesignCollection {
        $results = $this->api->get('gateway/window/v3/design/');

        $data = DataReader::arrayOr($results, 'data');
        $designs = [];
        foreach (array_keys($data) as $key) {
            $designs[] = new SimplePaymentWindowDesign(is_array($data[$key]) ? $data[$key] : []);
        }

        $collection = new PaymentWindowDesignCollection();
        $collection->paymentWindowDesigns = $designs;

        return $collection;
    }

}
