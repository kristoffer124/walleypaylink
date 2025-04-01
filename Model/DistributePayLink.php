<?php
declare(strict_types=1);

namespace Walley\PayLink\Model;

use GuzzleHttp\Client;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;
use Webbhuset\CollectorCheckout\Oath\AccessKeyManager;

class DistributePayLink
{
    private Client $httpClient;
    private ScopeConfigInterface $scopeConfig;
    private AccessKeyManager $accessKeyManager;

    public function __construct(
        Client $httpClient,
        ScopeConfigInterface $scopeConfig,
        AccessKeyManager $accessKeyManager
    ) {
        $this->httpClient       = $httpClient;
        $this->scopeConfig      = $scopeConfig;
        $this->accessKeyManager = $accessKeyManager;
    }

    public function send(string $sessionId, string $mobileNumber, int $storeId): void
    {
        $testMode = $this->scopeConfig->isSetFlag(
            'payment/walley_paylink/test_mode',
            ScopeInterface::SCOPE_STORE,
            $storeId
        );

        $baseUrl = $testMode
            ? 'https://api.uat.walleydev.com'
            : 'https://api.walleypay.com';

        $apiToken = $this->accessKeyManager->getAccessKeyByStore($storeId);

        $endpoint = sprintf('%s/checkouts/%s/paylink', rtrim($baseUrl, '/'), $sessionId);

        $payload = [
            'destination' => [
                'mobilePhoneNumber' => $mobileNumber
            ]
        ];
        $headers = [
            'Authorization' => 'Bearer ' . $apiToken,
            'Content-Type'  => 'application/json'
        ];

        $this->httpClient->post($endpoint, [
            'headers' => $headers,
            'json'    => $payload
        ]);
    }
}
