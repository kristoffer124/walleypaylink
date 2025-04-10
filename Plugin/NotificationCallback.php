<?php
namespace Walley\PayLink\Plugin;

use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Exception\NoSuchEntityException;
use Webbhuset\CollectorCheckout\Checkout\Order\Manager;
use Webbhuset\CollectorCheckout\Controller\Notification\Index;

class NotificationCallback
{
    private Manager $manager;
    private JsonFactory $jsonResult;
    private RequestInterface $request;

    public function __construct(
        Manager $manager,
        RequestInterface $request,
        JsonFactory $jsonFactory
    ) {
        $this->manager = $manager;
        $this->jsonResult = $jsonFactory;
        $this->request = $request;
    }

    public function aroundExecute(
        Index $subject,
        callable $proceed
    ) {
        $reference = $this->request->getParam('reference');
        try {
            $order = $this->manager->getOrderByPublicToken($reference);
            $paymentMethod = $order->getPayment()->getMethod();
            if ($paymentMethod !== 'walley_paylink') {
                return $proceed();
            }
        } catch (NoSuchEntityException $e) {
            return $proceed();
        }
        $jsonResult = $this->jsonResult->create();
        $jsonResult->setHttpResponseCode(200);
        $jsonResult->setData(['message' => 'Notification callback success']);
    }
}
