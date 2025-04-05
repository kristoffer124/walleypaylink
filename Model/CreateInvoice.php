<?php
declare(strict_types=1);

namespace Walley\PayLink\Model;

use Magento\Framework\DB\Transaction;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Model\Service\InvoiceService;

class CreateInvoice
{
    private InvoiceService $invoiceService;
    private Transaction $transaction;

    public function __construct(
        InvoiceService $invoiceService,
        Transaction $transaction
    ) {

        $this->invoiceService = $invoiceService;
        $this->transaction = $transaction;
    }

    public function execute(OrderInterface $order):bool
    {
        try {
            if (!$order->canInvoice()) {
                return false;
            }
            $invoice = $this->invoiceService->prepareInvoice($order);
            if (!$invoice || !$invoice->getTotalQty()) {
                return false;
            }
            $invoice->setIsOffline(true)
                ->register()
                ->save();
            $this->transaction
                ->addObject($invoice)
                ->addObject($order)
                ->save();
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }
}
