<?php

namespace EService\Payment\Controller\Hosted;


use EService\Payment\Helper\Helper;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Payment\Transaction;
use Magento\Sales\Model\Order\Email\Sender\OrderSender;

class Callback extends \Magento\Framework\App\Action\Action
{

    protected $resultPageFactory;

    protected  $invoiceService;



    /**
     * @var \Magento\Framework\DB\Transaction
     */
    protected $_transaction;

    /**
     * @var OrderSender
     */
    protected $orderSender;

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
        Helper $helper,
        \Magento\Sales\Model\Service\InvoiceService $invoiceService,
        \Magento\Framework\DB\Transaction $transaction,
        OrderSender $orderSender
    ) {
        parent::__construct($context);
        $this->_helper = $helper;
        $this->invoiceService = $invoiceService;
        $this->_transaction = $transaction;
        $this->orderSender = $orderSender;
        // Fix for Magento2.3 adding isAjax to the request params, CsrfAwareAction Magento2.3 compatibility
        // CsrfAwareAction Magento2.3 compatibility
        if (interface_exists("\Magento\Framework\App\CsrfAwareActionInterface")) {
            $request = $this->getRequest();
            if ($request instanceof \Magento\Framework\App\Request\Http && $request->isPost() && empty($request->getParam('form_key'))) {
                $formKey = $this->_objectManager->get(\Magento\Framework\Data\Form\FormKey::class);
                $request->setParam('form_key', $formKey->getFormKey());
            }
        }
    }

    /**
     * to handle the IPG Gateway callback when the payment finished
     *
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        $objectManager  = \Magento\Framework\App\ObjectManager::getInstance();
        $request        = $objectManager->get('\Magento\Framework\App\Request\Http');
        $requestPostPayload    = $request->getPost();
        $urlInterface = $objectManager->get('\Magento\Framework\UrlInterface');
        $raw_post = file_get_contents( 'php://input' );
        $parts = parse_url($raw_post);
        parse_str($parts['path'], $query);

        if(empty($query) || !isset($query['merchantTxId'])){
            //bad callback request
            return false;
        }

        if($query['action'] != 'AUTH' && $query['action'] != 'PURCHASE'){
            return false;
        }
        $orderId = $request->getParam('orderid');
        if(empty($orderId)){
            return false;
        }
        $orders = $objectManager->get('Magento\Sales\Model\Order');
        $order = $orders->loadByIncrementId($orderId);
        $params = array(
            "allowOriginUrl" => $urlInterface->getBaseUrl(),
            "merchantTxId" => $query['merchantTxId']
        );
        $gatewayTransaction = $this->_helper->executeGatewayTransaction("GET_STATUS", $params);
        if ($gatewayTransaction->result == 'success') {
            $realStatus = $gatewayTransaction->status;
            if ($realStatus == 'SET_FOR_CAPTURE' ||$realStatus == 'CAPTURED' ) { //PURCHASE was successful or transaction captured
                if($order->getStatus() != \Magento\Sales\Model\Order::STATE_PROCESSING && $order->getStatus() != \Magento\Sales\Model\Order::STATE_COMPLETE){
                    if($order->getState() == \Magento\Sales\Model\Order::STATE_PROCESSING){
                        return false;
                    }
                    // notify customer with the email
                    if (!$order->getEmailSent()) {
                        $this->orderSender->send($order);
                    }
                    $order->setState(\Magento\Sales\Model\Order::STATE_PROCESSING)
                    ->setStatus(\Magento\Sales\Model\Order::STATE_PROCESSING)
                    ->addStatusHistoryComment(__('Order status paid'))
                    ->setIsCustomerNotified(true);
                    
                    if($order->canInvoice()) {
                        $invoice = $order->prepareInvoice();
                        $invoice->register();
                        $invoice->setTransactionId($query['merchantTxId'])->pay()->save();
                        $order->addRelatedObject($invoice)->save();
                    }
                   
                    $payment = $order->getPayment();
                    $payment->setIsTransactionClosed(false)->setTransactionId($query['merchantTxId']);

                    $transaction = $payment->addTransaction(Transaction::TYPE_ORDER, null, true);
                    $transaction->setIsClosed(true);
                    $transaction->save();
                    $payment->save();
                    $order->save();
                }
            } else if ($realStatus == 'NOT_SET_FOR_CAPTURE') { // AUTH was successful
               
                // notify customer with the email
                if (!$order->getEmailSent()) {
                    $this->orderSender->send($order);
                }
                $order->setState(\Magento\Sales\Model\Order::STATE_PENDING_PAYMENT)
                ->setStatus(\Magento\Sales\Model\Order::STATE_PENDING_PAYMENT)
                ->addStatusHistoryComment(__('Order payment authorized'))
                ->setIsCustomerNotified(true);
                $order->save();
                $payment = $order->getPayment();
                $payment->setIsTransactionClosed(false);


                $payment->resetTransactionAdditionalInfo()
                ->setTransactionId($query['merchantTxId']);

                $transaction = $payment->addTransaction(Transaction::TYPE_AUTH, null, true);
                $transaction->setIsClosed(0);
                $transaction->save();
                $payment->save();
                // TODO: add auto-capture??
            }
        }
    }


}
