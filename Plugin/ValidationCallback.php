<?php
namespace Walley\PayLink\Plugin;

use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Walley\PayLink\Model\GetCheckoutInformation;
use Walley\PayLink\Model\UpdateShippingAddress;
use Walley\PayLink\Model\UpdateShippingMethod;
use Webbhuset\CollectorCheckout\Checkout\Order\Manager;
use Magento\Sales\Model\Service\InvoiceService;
use Magento\Framework\DB\Transaction;
use Webbhuset\CollectorCheckout\Controller\Validation\Index;

class ValidationCallback
{
    private RequestInterface $request;
    private Manager $manager;
    private JsonFactory $jsonResult;
    private UpdateShippingMethod $updateShippingMethod;
    private GetCheckoutInformation $getCheckoutInformation;
    private OrderRepositoryInterface $orderRepository;
    private UpdateShippingAddress $updateShippingAddress;

    public function __construct(
        RequestInterface $request,
        Manager $manager,
        JsonFactory $jsonResult,
        UpdateShippingAddress $updateShippingAddress,
        OrderRepositoryInterface $orderRepository,
        GetCheckoutInformation $getCheckoutInformation,
        UpdateShippingMethod $updateShippingMethod
    ) {
        $this->request = $request;
        $this->manager = $manager;
        $this->jsonResult = $jsonResult;
        $this->updateShippingMethod = $updateShippingMethod;
        $this->getCheckoutInformation = $getCheckoutInformation;
        $this->orderRepository = $orderRepository;
        $this->updateShippingAddress = $updateShippingAddress;
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
        if ($order->getState() !== 'new') {
            $jsonResult->setHttpResponseCode(400)
                ->setData(['message' => 'The order is not new']);
        }

        $checkoutData = $this->getCheckoutInformation->execute($order);
        $this->updateShippingMethod($order, $checkoutData);
        $this->updateShippingAddress($order, $checkoutData);
        $this->orderRepository->save($order);

        $jsonResult->setHttpResponseCode(200)
            ->setData(['message' => 'Validation callback approved']);

        return $jsonResult;
    }

    private function updateShippingMethod(OrderInterface $order, array $checkoutData):void
    {
        $shippingData = $this->getShippingData($checkoutData);
        if (empty($checkoutData)) {
            return;
        }
        $shippingDescription = $this->getShippingMethod($shippingData);
        $shippingMethod = $this->getShippingMethod($shippingData);
        if ((int) strlen($shippingDescription) === 0) {
            return;
        }
        $shippingAmount = $this->getShippingAmount($shippingData);
        $vat = $this->getShippingVat($shippingData);
        $this->updateShippingMethod->execute($order, $shippingMethod, $shippingDescription, $shippingAmount, $shippingData, $vat);
    }

    private function updateShippingAddress(OrderInterface $order, array $checkoutData)
    {
        $walleyAddress = $checkoutData['data']['customer']['deliveryAddress'];
        $address = [
            'firstname'  => $walleyAddress['firstName'],
            'lastname'   => $walleyAddress['lastName'],
            'street'     => [$walleyAddress['address'], $walleyAddress['address2'] ?? '', $walleyAddress['coAddress'] ?? ''],
            'postcode'   => $walleyAddress['postalCode'],
            'city'       => $walleyAddress['city'],
            'country_id' => $walleyAddress['countryCode'],
        ];
        $this->updateShippingAddress->execute($order, $address);
    }

    private function getShippingMethod(array $shippingData):string
    {
        return $shippingData['shipments'][0]['shippingChoice']['name'] ?? '';
    }

    private function getShippingAmount(array $shippingData):float
    {
        return (float) $shippingData['shipments'][0]['shippingChoice']['fee'] ?? 0.00;
    }

    private function getShippingVat(array $shippingData):float
    {
        return (float) $shippingData['shipments'][0]['shippingChoice']['vat'] ?? 0.00;
    }
    private function getShippingData(array $checkoutData): array
    {
        return $checkoutData['data']['shipping'] ?? [];
    }


}
