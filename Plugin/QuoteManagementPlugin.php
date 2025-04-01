<?php
namespace Walley\PayLink\Plugin;

use Magento\Framework\App\Area;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\QuoteManagement;
use Magento\Store\Model\App\Emulation;
use Magento\Store\Model\ScopeInterface;
use Walley\PayLink\Model\DistributePayLink;
use Walley\PayLink\Model\PaymentUriManager;
use Webbhuset\CollectorCheckout\Adapter;

class QuoteManagementPlugin
{
    protected $paymentUri;
    protected $privateId;
    private Emulation $emulation;
    private Adapter $adapter;
    private DistributePayLink $distributePayLink;
    private ScopeConfigInterface $scopeConfig;

    public function __construct(
        Emulation $emulation,
        DistributePayLink $distributePayLink,
        ScopeConfigInterface $scopeConfig,
        Adapter $adapter
    ) {
        $this->emulation = $emulation;
        $this->adapter = $adapter;
        $this->distributePayLink = $distributePayLink;
        $this->scopeConfig = $scopeConfig;
    }

    public function beforeSubmit(
        QuoteManagement $subject,
        Quote $quote,
        $orderData = []
    ) {
        if ($quote->getPayment() && $quote->getPayment()->getMethod() === 'walley_paylink') {
            $storeId = $quote->getStoreId();
            $this->emulation->startEnvironmentEmulation($storeId, Area::AREA_FRONTEND, true);
            $result = $this->adapter->initialize($quote);
            $this->paymentUri = $result->getPaymentUri();
            $this->privateId = $result->getPrivateId();
            $this->emulation->stopEnvironmentEmulation();
        }
        return [$quote, $orderData];
    }

    public function afterSubmit(
        QuoteManagement $subject,
        $result,
        Quote $quote,
        $orderData = []
    ) {
        if ($quote->getPayment() && $quote->getPayment()->getMethod() === 'walley_paylink') {
            $payment = $result->getPayment();
            $payment->setAdditionalInformation(
                'walley_paylink',
                [
                    'paymentLink' => $this->paymentUri,
                    'sessionId' => $this->privateId
                ]
            );
            $payment->save();
            $phoneNumber = (string) $quote->getShippingAddress()->getTelephone();
            $storeId = (int) $quote->getStoreId();
            $distributeSMS = $this->scopeConfig->isSetFlag(
                'payment/walley_paylink/sms_distribute',
                ScopeInterface::SCOPE_STORE,
                $storeId
            );
            if ($distributeSMS) {
                $this->distributePayLink->send($this->privateId, $phoneNumber, $storeId);
            }
        }
        return $result;
    }
}
