<?php
declare(strict_types=1);

namespace Walley\PayLink\Model;

use Magento\Sales\Api\Data\OrderInterface;
use Webbhuset\CollectorCheckout\Data\OrderHandler;

class UpdateShippingMethod
{
    private OrderHandler $orderHandler;

    public function __construct(
        OrderHandler $orderHandler
    ) {
        $this->orderHandler = $orderHandler;
    }

    public function execute(
        OrderInterface $order,
        string $newShippingMethod,
        string $newShippingDescription,
        float $newShippingAmount,
        array $shippingData,
        float $vat
    ): void {
        $shippingAddress = $order->getShippingAddress();
        if (!$shippingAddress) {
            throw new \RuntimeException('No shipping address.');
        }
        $order->setShippingMethod($newShippingMethod)
            ->setShippingDescription($newShippingDescription);
        $oldNet = (float)$shippingAddress->getShippingAmount();
        $oldTax = (float)$shippingAddress->getShippingTaxAmount();
        $oldIncl = $oldNet + $oldTax;
        $taxRate = $vat / 100;
        $newNet = $newShippingAmount / (1 + $taxRate);
        $newTax = $newShippingAmount - $newNet;
        $diff = $newShippingAmount - $oldIncl;
        $shippingAddress->setShippingMethod($newShippingMethod)
            ->setShippingDescription($newShippingDescription)
            ->setShippingAmount($newNet)
            ->setBaseShippingAmount($newNet)
            ->setShippingTaxAmount($newTax)
            ->setBaseShippingTaxAmount($newTax)
            ->setShippingInclTax($newShippingAmount)
            ->setBaseShippingInclTax($newShippingAmount);
        $order->setShippingAmount($newNet)
            ->setBaseShippingAmount($newNet)
            ->setShippingInclTax($newShippingAmount)
            ->setBaseShippingInclTax($newShippingAmount)
            ->setTaxAmount($order->getTaxAmount() - $oldTax + $newTax)
            ->setBaseTaxAmount($order->getBaseTaxAmount() - $oldTax + $newTax)
            ->setGrandTotal($order->getGrandTotal() + $diff)
            ->setBaseGrandTotal($order->getBaseGrandTotal() + $diff);
        $this->orderHandler->setDeliveryCheckoutShipmentData($order, $shippingData);
        $order->addCommentToStatusHistory("Shipment method updated by customer", false, false);
    }
}
