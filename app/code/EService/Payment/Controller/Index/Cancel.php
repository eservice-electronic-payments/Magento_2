<?php

namespace EService\Payment\Controller\Hosted;

class Cancel extends \Magento\Framework\App\Action\Action
{
    /**
     * @var Checkout
     */
    private $checkoutHelper;
    protected $resultPageFactory;
    /**
     * @param Context $context
     * @param Checkout $checkoutHelper
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Sales\Model\Order $orderModel,
        \Magento\Framework\View\Result\PageFactory $resultPageFactory
    ) {
        parent::__construct($context);
        $this->orderModel = $orderModel;
        $this->resultPageFactory =$resultPageFactory;
    }

    /**
     * Customer canceled payment on gateway side.
     *
     * @return void
     */
    public function execute()
    {
        $this->orderModel->cancel('');
        return $resultPage = $this->resultPageFactory->create();
    }
}
