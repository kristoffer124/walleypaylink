<?php
declare(strict_types=1);

namespace Walley\PayLink\Model;

use Magento\Sales\Api\Data\OrderInterface;

class UpdateShippingAddress
{
    public function execute(OrderInterface $order, array $address)
    {
        $order->getShippingAddress()->addData($address);
    }
}
