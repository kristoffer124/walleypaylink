<?php
declare(strict_types=1);

namespace Walley\PayLink\Model;

use Magento\Sales\Api\Data\OrderInterface;
use Webbhuset\CollectorCheckout\Config\OrderConfigFactory;
use Webbhuset\CollectorCheckout\Adapter;

class GetCheckoutInformation
{
    private OrderConfigFactory $configFactory;
    private Adapter $adapter;

    public function __construct(
        OrderConfigFactory $orderConfigFactory,
        Adapter $adapter
    ) {
        $this->configFactory = $orderConfigFactory;
        $this->adapter = $adapter;
    }

    public function execute(OrderInterface $order):array
    {
        $config = $this->configFactory->create(['order' => $order]);
        $collectorBankPrivateId = $order->getData('collectorbank_private_id');
        $adapter = $this->adapter->getAdapter($config);

        return $adapter->acquireInformation($collectorBankPrivateId);
    }
}
