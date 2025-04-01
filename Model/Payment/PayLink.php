<?php
namespace Walley\PayLink\Model\Payment;

use Magento\Payment\Model\Method\AbstractMethod;

class PayLink extends AbstractMethod
{
    protected $_code = 'walley_paylink';
    protected $_isOffline = true;
}
