<?php

namespace EService\Payment\Controller\Hosted;

use EService\Payment\Helper\Configurable;
use EService\Payment\Helper\Helper;
use EService\Payment\Helper\Payments;
use EService\Payment\Helper\RequestFactory;

class Ipgdirect extends \Magento\Framework\App\Action\Action
{

    protected $resultPageFactory;

    /**
     * @var \EService\Payment\Helper\Helper
     */
    private $_helper;

    /**
     * Constructor
     *
     * @param \Magento\Framework\App\Action\Context $context
     * @param \Magento\Framework\View\Result\PageFactory $resultPageFactory
     * @param Helper $helper
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Framework\View\Result\PageFactory $resultPageFactory,
        Helper $helper
    ) {
        parent::__construct($context);
        $this->_helper = $helper;
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
        $requestPostPayload    = $request->getPost();
		$urlInterface   = $objectManager->get('\Magento\Framework\UrlInterface');

		if(isset($requestPostPayload) && isset($requestPostPayload['result'])) {
            if($requestPostPayload['result'] == 'success') {
                if ($requestPostPayload['merchantTxId'] != $request->getParam('orderid'))
                {
                    $this->_redirect('checkout/cart');
                    return;
                }
                $orderId   = $requestPostPayload['merchantTxId'];
                $orders = $objectManager->get('Magento\Sales\Model\Order');
                $order = $orders->loadByIncrementId($orderId);

                $payment = $order->getPayment();

				$payment->setLastTransId($requestPostPayload['merchantTxId']);
				$payment->setTransactionId($requestPostPayload['merchantTxId']);
				$payment->setAdditionalInformation(
						[\Magento\Sales\Model\Order\Payment\Transaction::RAW_DETAILS => (array) $requestPostPayload]
					);

				$payment->setAmountAuthorized($requestPostPayload['acquirerAmount']);
				$action = $requestPostPayload['action'];
				$this->handleAction($action, $payment, $order);

				$trans = $objectManager->get('Magento\Sales\Model\Order\Payment\Transaction\Builder');
				$transaction = $trans->setPayment($payment)
					->setOrder($order)
					->setTransactionId($requestPostPayload['merchantTxId'])
					->setAdditionalInformation(
						[\Magento\Sales\Model\Order\Payment\Transaction::RAW_DETAILS => (array) $requestPostPayload]
					)
					->setFailSafe(true)
					->build($this->_helper->getMagentoTransactionType($action));
		 
				$payment->setParentTransactionId(null);
				$payment->save();
				
				$order->setState("processing")->setStatus("processing")->addStatusHistoryComment('Payment completed successfully.')->setIsCustomerNotified(true);
				$order->save();
				$redirectUrl = $urlInterface->getUrl('checkout/onepage/success/');
				$this->_redirect($redirectUrl);

				return;
			} else if($requestPostPayload['result'] == 'failure') {
                if ($requestPostPayload['merchantTxId'] != $request->getParam('orderid'))
                {
                    $this->_redirect('checkout/cart');
                    return;
                }
                $orderId   = $requestPostPayload['merchantTxId'];
                $orders = $objectManager->get('Magento\Sales\Model\Order');
                $order = $orders->loadByIncrementId($orderId);
                $this->_helper->cancelOrder($order);
                $this->_redirect('checkout/cart');
			} else {
                $redirectUrl = $urlInterface->getUrl('checkout/onepage/failure/');
                $this->_redirect($redirectUrl);

                return;
			}
        }
    }

    protected function getGatewayTransaction($order) {
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $urlInterface = $objectManager->get('\Magento\Framework\UrlInterface');

        $ipgApi = $this->_helper->constructIPG();
        $checkStatus = $ipgApi->get_status()
            ->allowOriginUrl($urlInterface->getBaseUrl())
            ->timestamp(time() * 1000)
            //->txId($order->getId())
            ->merchantTxId($order->getRealOrderId());

        $r = $checkStatus->execute();

        return $r;
    }

    private function handleAction($action, $payment, $order)
    {
        switch ($action){
            case Configurable::ACTION_AUTH:
                // $payment->capture($order);
        }
    }
}
