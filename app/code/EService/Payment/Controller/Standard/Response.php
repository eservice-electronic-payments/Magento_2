<?php
namespace EService\Payment\Controller\Standard;

class Response extends \Magento\Framework\App\Action\Action
{

    protected $resultPageFactory;

    /**
     * Constructor
     *
     * @param \Magento\Framework\App\Action\Context  $context
     * @param \Magento\Framework\View\Result\PageFactory $resultPageFactory
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Framework\View\Result\PageFactory $resultPageFactory
    ) {
        parent::__construct($context);

    }

    /**
     * Execute view action
     *
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        $objectManager  = \Magento\Framework\App\ObjectManager::getInstance();
        $request        = $objectManager->get('\Magento\Framework\App\Request\Http');
        $requestData =$request->getPost();
        $orderid =$request->getParam('orderid');

        $order = $objectManager->create('\Magento\Sales\Model\Order')->load($orderid);
        $urlInterface   = $objectManager->get('\Magento\Framework\UrlInterface');
        print_r($requestData);die;

        if($requestData['result'] == 'success')
        {
            $orders = $objectManager->get('Magento\Sales\Model\Order');
            $order = $orders->loadByIncrementId($orderid);

            $order->setState("processing")->setStatus("processing")->addStatusHistoryComment('Order status processing')->setIsCustomerNotified(true);
            $order->save();
            $url        = $urlInterface->getUrl('checkout/onepage/success/');
            $this->_redirect($url);
            return;
        }
        else if($requestData['result'] == 'error')
        {
            $orders = $objectManager->get('Magento\Sales\Model\Order');
            $order = $orders->loadByIncrementId($orderid);
            $order->setState("canceled")->setStatus("canceled")->addStatusHistoryComment('Order cancelled due to failed transaction')->setIsCustomerNotified(true);
            $order->save();

            $url        = $urlInterface->getUrl('eservice_payment/standard/cancel');
            $this->_redirect($url);
        }

    }


}
