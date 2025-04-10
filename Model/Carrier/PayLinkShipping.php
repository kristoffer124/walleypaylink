<?php
namespace Walley\PayLink\Model\Carrier;

use Magento\Backend\App\Area\FrontNameResolver;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\State;
use Magento\Quote\Model\Quote\Address\RateRequest;
use Magento\Quote\Model\Quote\Address\RateResult\ErrorFactory;
use Magento\Quote\Model\Quote\Address\RateResult\MethodFactory;
use Magento\Sales\Model\Order;
use Magento\Shipping\Model\Carrier\AbstractCarrier;
use Magento\Shipping\Model\Carrier\CarrierInterface;
use Magento\Shipping\Model\Rate\ResultFactory;
use Psr\Log\LoggerInterface;

class PayLinkShipping extends AbstractCarrier implements CarrierInterface
{
    protected $_code = 'walley_paylink_shipping';
    private State $appState;
    private ResultFactory $rateResultFactory;
    private MethodFactory $rateMethodFactory;

    public function __construct(
        ScopeConfigInterface $scopeConfig,
        ErrorFactory $rateErrorFactory,
        LoggerInterface $logger,
        State $appState,
        ResultFactory $rateResultFactory,
        MethodFactory $rateMethodFactory,
        array $data = []
    ) {
        parent::__construct($scopeConfig, $rateErrorFactory, $logger, $data);
        $this->appState = $appState;
        $this->rateResultFactory = $rateResultFactory;
        $this->rateMethodFactory = $rateMethodFactory;
    }

    public function collectRates(RateRequest $request)
    {
        if (!$this->getConfigData('active')) {
            return false;
        }
        if ($this->appState->getAreaCode() !== FrontNameResolver::AREA_CODE) {
            return false;
        }
        $result = $this->rateResultFactory->create();
        $method = $this->rateMethodFactory->create();
        $method->setCarrier($this->_code)
            ->setCarrierTitle($this->getConfigData('title'))
            ->setMethod($this->_code)
            ->setMethodTitle($this->getConfigData('title'))
            ->setPrice($this->getConfigData('price'));

        $result->append($method);
        return $result;
    }

    public function getAllowedMethods()
    {
        return [];
    }
}
