<?php

namespace EService\Payment\Block;

class Response extends \Magento\Framework\View\Element\Template
{
    const REGISTRY_PARAMS_KEY = 'eservice_payment_params';

    /**
     * Registry
     *
     * @var \Magento\Framework\Registry
     */
    private $registry;

    /**
     * GatewayResponse constructor.
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Magento\Framework\Registry $registry,
        array $data = []
    ) {
        $this->registry = $registry;
        parent::__construct($context, $data);
    }

    /**
     * @return \Magento\Framework\View\Element\Template
     */
    public function _prepareLayout()
    {
        $params = $this->registry->registry(self::REGISTRY_PARAMS_KEY);
        $this->setParams($params);

        return parent::_prepareLayout();
    }
}
