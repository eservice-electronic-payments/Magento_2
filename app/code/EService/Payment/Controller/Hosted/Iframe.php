<?php

namespace EService\Payment\Controller\Hosted;
use EService\Payment\Helper\Helper;

class Iframe extends \Magento\Framework\App\Action\Action
{
    private $_helper;
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        Helper $helper
    ) {
        parent::__construct($context);
        $this->_helper = $helper;
    }
    /**
     * Set redirect.
     */
    public function execute()
    {
        $mode = $this->_helper->getIntegrationMode();
        if($mode !== 'iframe'){
            $this->_redirect('checkout/cart');
            return;
        }
        $this->_view->loadLayout();
        $this->_view->getLayout()->initMessages();
        $this->_view->renderLayout();
    }
}
