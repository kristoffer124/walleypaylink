<?php
namespace Walley\PayLink\Plugin;

use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Exception\NoSuchEntityException;
use Walley\PayLink\Model\CreateInvoice;
use Walley\PayLink\Model\GetCheckoutInformation;
use Webbhuset\CollectorCheckout\Checkout\Order\Manager;
use Webbhuset\CollectorCheckout\Controller\Notification\Index;

class NotificationCallback
{
    private Manager $manager;
    private JsonFactory $jsonResult;
    private RequestInterface $request;
    private CreateInvoice $createInvoice;

    public function __construct(
        Manager $manager,
        CreateInvoice $createInvoice,
        RequestInterface $request,
        GetCheckoutInformation $getCheckoutInformation,
        JsonFactory $jsonFactory
    ) {
        $this->manager = $manager;
        $this->jsonResult = $jsonFactory;
        $this->request = $request;
        $this->getCheckoutInformation = $getCheckoutInformation;
        $this->createInvoice = $createInvoice;
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
        $this->createInvoice->execute($order);
        $jsonResult = $this->jsonResult->create();
        $jsonResult->setHttpResponseCode(200);
        $jsonResult->setData(['message' => 'Notification callback success']);
    }
}
