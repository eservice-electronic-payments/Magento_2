<?php
namespace EService\Payment\Block;
class Cancel extends \Magento\Framework\View\Element\Template
{
    protected $checkoutSession;
    protected $_checkoutSession;
    protected $_urlInterface;
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
	\Magento\Checkout\Model\Session $checkoutSession,
    \Magento\Framework\UrlInterface $urlInterface
    ) {
	$this->_checkoutSession = $checkoutSession;
    $this->_urlInterface = $urlInterface;
        parent::__construct($context);

    }

    public function cancel()
    {
        return __('IPG redirect');
    }

    public function getPageURL()
    {
        return $this->_urlInterface;
    }
}
