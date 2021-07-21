<?php

namespace EService\Payment\Block\Onepage;

class Success extends \Magento\Framework\View\Element\Template
{
    private $_checkoutSession;
    private $_customerSession;

    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magento\Customer\Model\Session $customerSession,
        array $data = []
    ) {
        $this->_checkoutSession = $checkoutSession;
        $this->_customerSession = $customerSession;
        parent::__construct($context, $data);
    }

    protected function _toHtml()
    {
        $customerId = $this->_customerSession->getCustomerId();
        $order = $this->_checkoutSession->getLastRealOrder();
        if (!$order) {
            return '';
        }
        if ($order->getId()) {
            if ($order->getPayment()->getMethodInstance()->getCode() == 'eservice_payment') {
                $this->addData(
                    [
                    'is_eservice' => true
                    ]
                );

                return parent::_toHtml();
            }
        }

        return '';
    }
}
