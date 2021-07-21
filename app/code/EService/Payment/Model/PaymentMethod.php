<?php

namespace EService\Payment\Model;


use EService\Payment\Model\Config\Source\Brand;
use EService\Payment\Helper\Helper;
use EService\Payment\Model\Config\Source\NewOrderPaymentActions;
use EService\Payment\Model\Config\Source\DisplayMode;
use Magento\Framework\DataObject;
use Magento\Payment\Model\InfoInterface;
use Magento\Payment\Model\Method\ConfigInterface;
use Magento\Payment\Model\Method\Online\GatewayInterface;
use Magento\Sales\Model\Order\Payment\Transaction;

class PaymentMethod extends \Magento\Payment\Model\Method\AbstractMethod implements GatewayInterface
{
    const METHOD_CODE = 'eservice_payment';
    const NOT_AVAILABLE = 'N/A';

    /**
     * @var string
     */
    protected $_code = self::METHOD_CODE;

    /**
     * Payment Method feature.
     *
     * @var bool
     */
    protected $_canAuthorize = false;

    /**
     * @var bool
     */
    protected $_canCapture = true;

    /**
     * @var bool
     */
    protected $_canCapturePartial = true;

    /**
     * @var bool
     */
    protected $_canCaptureOnce = true;

    /**
     * @var bool
     */
    protected $_canRefund = true;

    /**
     * @var bool
     */
    protected $_canRefundInvoicePartial = true;

    /**
     * @var bool
     */
    protected $_isGateway = true;

    /**
     * @var bool
     */
    protected $_isInitializeNeeded = true;

    /**
     * @var bool
     */
    protected $_canUseInternal = true;

    /**
     * @var bool
     */
    protected $_canVoid = true;


    protected $_canCancelInvoice = true;

    /**
     * @var bool
     */
    protected $_canReviewPayment = false;
    /**
     * @var \Magento\Framework\App\ProductMetadataInterface
     */
    protected $_productMetadata;
    /**
     * @var \Magento\Framework\Module\ResourceInterface
     */
    protected $_resourceInterface;

    protected  $invoiceService;

    /**
     * @var \EService\Payment\Helper\Helper
     */
    private $_helper;
    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    private $_storeManager;
    /**
     * @var \Magento\Framework\UrlInterface
     */
    private $_urlBuilder;
    /**
     * @var \Magento\Framework\Locale\ResolverInterface
     */
    private $_resolver;
    /**
     * @var \Psr\Log\LoggerInterface
     */
    private $_ipgLogger;
    /**
     * @var \Magento\Checkout\Model\Session
     */
    private $_session;

    /**
     * @var \Magento\Customer\Api\CustomerRepositoryInterface
     */
    private $_customerRepository;

    /**
     * PaymentMethod constructor.
     *
     * @param \Magento\Framework\App\RequestInterface                      $request
     * @param \Magento\Framework\UrlInterface                              $urlBuilder
     * @param Helper                                                       $helper
     * @param \Magento\Store\Model\StoreManagerInterface                   $storeManager
     * @param \Magento\Framework\Locale\ResolverInterface                  $resolver
     * @param \Magento\Framework\Model\Context                             $context
     * @param \Magento\Framework\Registry                                  $registry
     * @param \Magento\Framework\Api\ExtensionAttributesFactory            $extensionFactory
     * @param \Magento\Framework\Api\AttributeValueFactory                 $customAttributeFactory
     * @param \Magento\Payment\Helper\Data                                 $paymentData
     * @param \Magento\Framework\App\Config\ScopeConfigInterface           $scopeConfig
     * @param \Magento\Payment\Model\Method\Logger                         $logger
     * @param \Psr\Log\LoggerInterface                                     $ipgLogger
     * @param \Magento\Framework\App\ProductMetadataInterface              $productMetadata
     * @param \Magento\Framework\Module\ResourceInterface                  $resourceInterface
     * @param \Magento\Checkout\Model\Session                              $session
     * @param \Magento\Customer\Api\CustomerRepositoryInterface            $customerRepository
     * @param \Magento\Framework\Model\ResourceModel\AbstractResource|null $resource
     * @param \Magento\Framework\Data\Collection\AbstractDb|null           $resourceCollection
     * @param array                                                        $data
     */
    public function __construct(
        \Magento\Framework\App\RequestInterface $request,
        \Magento\Framework\UrlInterface $urlBuilder,
        Helper $helper,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\Locale\ResolverInterface $resolver,
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Api\ExtensionAttributesFactory $extensionFactory,
        \Magento\Framework\Api\AttributeValueFactory $customAttributeFactory,
        \Magento\Payment\Helper\Data $paymentData,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Payment\Model\Method\Logger $logger,
        \Psr\Log\LoggerInterface $ipgLogger,
        \Magento\Framework\App\ProductMetadataInterface $productMetadata,
        \Magento\Framework\Module\ResourceInterface $resourceInterface,
        \Magento\Checkout\Model\Session $session,
        \Magento\Customer\Api\CustomerRepositoryInterface $customerRepository,
        \Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
        \Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        parent::__construct(
            $context,
            $registry,
            $extensionFactory,
            $customAttributeFactory,
            $paymentData,
            $scopeConfig,
            $logger,
            $resource,
            $resourceCollection,
            $data
        );
        $this->_urlBuilder = $urlBuilder;
        $this->_helper = $helper;
        $this->_storeManager = $storeManager;
        $this->_resolver = $resolver;
        $this->_request = $request;
        $this->_ipgLogger = $ipgLogger;
        $this->_productMetadata = $productMetadata;
        $this->_resourceInterface = $resourceInterface;
        $this->_session = $session;
        $this->_customerRepository = $customerRepository;
        //$this->invoiceService = $invoiceService;
    }

    /**
     * @param string $paymentAction
     * @param object $stateObject
     *
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function initialize($paymentAction, $stateObject)
    {
        /*
         * do not send order confirmation mail after order creation wait for
         * result confirmation
         */
        $payment = $this->getInfoInstance();
        $order = $payment->getOrder();
        $order->setCanSendNewEmailFlag(false);

        $stateObject->setState(\Magento\Sales\Model\Order::STATE_NEW);
        $stateObject->setStatus($this->_helper->getConfigData('order_status'));
        $stateObject->setIsNotified(false);
    }

    /**
     * Retrieve payment method title
     *
     * @return string
     */
    public function getTitle()
    {
        $title_code = $this->getConfigData('title');
        $brands = (new Brand())->toOptionArray();
        foreach ($brands as $brand) {
            if($brand['value'] == $title_code) {
                return $brand['label'];
            }
        }

        return $title_code;
    }

    /**
     * Checkout redirect URL.
     *
     * @see \Magento\Checkout\Controller\Onepage::savePaymentAction()
     * @see \Magento\Quote\Model\Quote\Payment::getCheckoutRedirectUrl()
     *
     * @return string
     */
    public function getCheckoutRedirectUrl()
    {
        $displayMode = $this->getConfigData('display_mode');
       if ($displayMode === DisplayMode::DISPLAY_MODE_IFRAME) {
           $redirectUrl = 'eservice/hosted/iframe';
        } else {
            $redirectUrl = 'eservice/hosted/redirect';
        }
        return $this->_urlBuilder->getUrl(
            $redirectUrl
        );
    }

    /**
     * Post request to gateway and return response.
     *
     * @param DataObject      $request
     * @param ConfigInterface $config
     */
    public function postRequest(DataObject $request, ConfigInterface $config)
    {
        // Do nothing
        $this->_helper->logDebug('Gateway postRequest called');
    }

    /**
     * @desc Get form url
     *
     * @return string
     */
    public function getFormUrl()
    {
        return $this->_helper->getFormUrl();
    }

    /**
     * @desc Get form method
     *
     * @return string
     */
    public function getFormMethod()
    {
        return $this->_helper->getFormMethod();
    }

    /**
     * @desc Form fields that will be sent with the request
     *
     * @return array
     *
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getFormFields()
    {
        $paymentAction = $this->_helper->getConfigData('payment_action');
        $formFields = $this->getAPIParametersForRedirect($this->toAPIOperation($paymentAction));

        return $formFields;
    }

    /**
     * @param $apiOperation
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Validator\Exception
     */
    private function getAPIParametersForRedirect($apiOperation)
    {
        $sessionTokenData = $this->getTokenHostedData($apiOperation);
        $token = $this->_helper->executeGatewayTransaction($sessionTokenData['action'],$sessionTokenData);
        if ($token->result != 'success' ) {
            throw new \Magento\Framework\Validator\Exception(__(json_encode($token->errors)));
        }
        $merchantId = trim($this->_helper->getConfigData('merchant_id'));
        $formFields = [];
        $formFields['token'] = $token->token;
        $formFields['merchantId'] = $merchantId;

        $paymentMethodID = trim($this->_helper->getConfigData('payment_method'));
        if ($paymentMethodID != '') {
            $formFields['paymentSolutionId'] = $paymentMethodID;
        }
        $formFields['integrationMode'] = $this->_helper->getIntegrationMode();

        return $formFields;
    }

    private function getTokenHostedData($apiOperation)
    {
        $payment = $this->getInfoInstance();
        $order = $payment->getOrder();

        // allow origin url
        // to get only the site's domain url name to assign to the parameter allowOriginUrl .
        //otherwise it will encounter a CORS issue when it is deployed inside a subfolder of the web server.
        $url = $this->_urlBuilder->getBaseUrl();
        $parse_result = parse_url($url);
        if(isset($parse_result['port'])){
            $allowOriginUrl = $parse_result['scheme']."://".$parse_result['host'].":".$parse_result['port'];
        }else{
            $allowOriginUrl = $parse_result['scheme']."://".$parse_result['host'];
        }
        // currency code
        $orderCurrencyCode = $order->getBaseCurrencyCode();
        // amount
        $amount = $this->formatAmount($order->getBaseGrandTotal());
        // merchant transaction id or order id
        $orderId = $order->getRealOrderId();
        if(strlen($orderId) > 50) {
            $orderId = substr($orderId, -50);
        }
        // customer id
        $customerId = $order->getCustomerId();
        if ($customerId == '') {
            $customerId = 'guest_'.$orderId;
        }
        if(strlen($customerId) > 20) {
            $customerId = substr($customerId, -20);
        }

        // billing address
        $billingAddress = $order->getBillingAddress();
        if ($billingAddress) {
            if (is_array($billingAddress->getStreet(1))) {
                $street_arr = $billingAddress->getStreet(1);
                $billingAddressStreet = substr($street_arr[0], 0, 50);
            } else {
                $billingAddressStreet = $billingAddress->getStreet(1);
            }
            $billingAddressCity = $billingAddress->getCity();
            $billingAddressCountry = $billingAddress->getcountryId();
            $billingAddressPostalCode = $billingAddress->getPostcode();
            $billingAddressPhone = $billingAddress->getTelephone();
        }

        // shipping address
        $shippingAddress = $order->getShippingAddress();
        if ($shippingAddress) {
            if (is_array($shippingAddress->getStreet(1))) {
                $street_arr1 = $billingAddress->getStreet(1);
                $shippingAddressStreet = substr($street_arr1[0], 0, 40);
            } else {
                $shippingAddressStreet = $billingAddress->getStreet(1);
            }
            $shippingAddressCity = $shippingAddress->getCity();
            $shippingAddressCountry = $shippingAddress->getcountryId();
            $shippingAddressPostalCode = $shippingAddress->getPostcode();
            $shippingAddressPhone = $shippingAddress->getTelephone();
        }

        if ($billingAddress) {
            if ($billingAddressCountry == '') {
                if ($shippingAddress) {
                    $billingAddressCountry = $shippingAddressCountry;
                }
            }
        }

        // merchant notification URL: server-to-server, URL to which the Transaction Result Call will be sent
        $merchantNotificationUrl = $this->_urlBuilder->getUrl($this->_helper->getNotificationRoute($order->getRealOrderId()));
        // The URL to which the customer’s browser is redirected after the payment
        $merchantLandingPageUrl = $this->_urlBuilder->getUrl($this->_helper->getLandingPageOnReturnAfterRedirect($order->getRealOrderId()));
        // add to session in order to be retrieved on return
        if ($this->_session->getOrderId()) {
            $this->_session->unsOrderId();
        }
        $this->_session->setOrderId($order->getRealOrderId());

        // merchant reference
        $merchantReference = $order->getRealOrderId();

        // customer data
        $customerFirstName = $billingAddress->getFirstname();
        $customerLastName = $billingAddress->getLastname();
        $customerEmail = $billingAddress->getEmail();
        $customerPhone = $billingAddress->getTelephone();
        $customerAddressCountry = $billingAddressCountry;
        $customerAddressCity = $billingAddressCity;
        $customerAddressStreet = $billingAddressStreet;
        $customerAddressPostalCode = $billingAddress->getPostcode();
        $customerAddressState = $billingAddress->getRegionCode();

        $sessionTokenData = array(
            "action" => $apiOperation,
            "merchantTxId" => $orderId,
            "customerId" => $customerId,
            "channel" => "ECOM",
            "amount" => $amount,
            "currency" => $orderCurrencyCode,
            "country" => $billingAddressCountry,
            "allowOriginUrl" => $allowOriginUrl,
            "merchantNotificationUrl" => $merchantNotificationUrl,
            "merchantLandingPageUrl" => $merchantLandingPageUrl,
            "customerFirstName" => $customerFirstName,
            "customerLastName" => $customerLastName,
            "customerEmail" => $customerEmail,
            "customerPhone" => $customerPhone,
            "userDevice" => 'DESKTOP',
            "userAgent" => getenv('HTTP_USER_AGENT'),
            "customerIPAddress" => $order->getRemoteIp(),
            "customerAddressHouseName" => $customerAddressStreet,
            "customerAddressStreet" => $customerAddressStreet,
            "customerAddressCity" => $customerAddressCity,
            "customerAddressPostalCode" => $customerAddressPostalCode,
            "customerAddressState" => $customerAddressState,
            "customerAddressCountry" => $customerAddressCountry,
            "merchantChallengeInd" => '01',
            "merchantDecReqInd" => 'N',
            "merchantLandingPageRedirectMethod" => 'GET'

        );

        $sessionTokenData["paymentSolutionId"] = '';
        // brand id
        $brandId = $this->_helper->getConfigData('merchant_brandid');
        if (trim($brandId) != '') {
            $sessionTokenData['brandId'] = trim($brandId);
        }

        // language
        $locale = $this->_resolver->getLocale();
        if ($locale != '') {
            $language = \Locale::getPrimaryLanguage($locale);
            //$country = \Locale::getRegion($locale);
            $sessionTokenData["language"] = $language;
        }

        return $sessionTokenData;
    }

    public function formatAmount($amount, $asFloat = false)
    {
        return number_format((float)$amount, 2, '.', '');
    }

    /**
     * Gets the session token
     *
     * @param array $tokenData
     * @return PaymentMethod $array
     * @throws \Magento\Framework\Validator\Exception
     */
    private function getToken($tokenData = array())
    {
        try {
            $tokenRequest = RequestFactory::newTokenRequest($tokenData['action'], $tokenData);
            // this sets static fields of config
            $tokenRequest = $this->_helper->setCommonParams($tokenRequest);
 /*           foreach($tokenData as $key => $value) {
                call_user_func_array(array($tokenRequest, $key), array($value));
            }*/
            $token = $tokenRequest->execute();
            return $token;

        } catch (\Exception $e) {
            throw new \Magento\Framework\Validator\Exception(__($e->getMessage()));
        }
    }

    private function toAPIOperation($paymentAction) {
        switch ($paymentAction) {
            case NewOrderPaymentActions::PAYMENT_ACTION_AUTH: {
                return "AUTH";
            }
            case NewOrderPaymentActions::PAYMENT_ACTION_SALE: {
                return "PURCHASE";
            }
            default: {
                return strtoupper($paymentAction);
            }
        }
    }
    public function getMerchantNotificationUrl(){
        // merchant notification URL: server-to-server, URL to which the Transaction Result Call will be sent
        $merchantNotificationUrl = $this->_urlBuilder->getUrl($this->_helper->getNotificationRoute($order->getRealOrderId()));
        return $merchantNotificationUrl;

    }
    public function getMerchantLandingPageUrl(){
        $payment = $this->getInfoInstance();
        $order = $payment->getOrder();
        // The URL to which the customer’s browser is redirected after the payment
        $merchantLandingPageUrl = $this->_urlBuilder->getUrl($this->_helper->getLandingPageOnReturnAfterRedirect($order->getRealOrderId()));
        return $merchantLandingPageUrl;
    }

    /**
     * Authorize payment method
     *
     * @param \Magento\Framework\DataObject|InfoInterface $payment
     * @param float $amount
     * @return $this
     * @throws \Magento\Framework\Exception\LocalizedException
     * @api
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     * @deprecated 100.2.0
     */
    public function authorize(\Magento\Payment\Model\InfoInterface $payment, $amount)
    {
        parent::authorize($payment, $amount);

        // TODO: tokenize card and add the token to the token request

        $data = array(
            "amount" => $amount
        );
        $sessionTokenData = $this->getTokenHostedData("AUTH", $data);
        // direct api supports only 500
        $sessionTokenData["paymentSolutionId"] = '500';
        $token = $this->getToken($sessionTokenData);
        if ($token->result != 'success' ) {
            throw new \Magento\Framework\Validator\Exception(__(json_encode($token->errors)));
        }
        $params = $this->getAPIParametersForDirect("AUTH", $data);
        $params['token'] = $token->token;
        $result = $this->_helper->executeGatewayTransaction("AUTH", $params);
        if($result->result === 'success') {
            $payment->setTransactionId($result->merchantTxId)
                ->setIsTransactionClosed(false)
                ->setTransactionAdditionalInfo(\Magento\Sales\Model\Order\Payment\Transaction::RAW_DETAILS, $result);
            $order = $payment->getOrder();
            $transactionStatus = $result->status;
            if ($transactionStatus === 'NOT_SET_FOR_CAPTURE') { // auth was successful
                $order->setState("processing")
                    ->setStatus("processing")
                    ->addStatusHistoryComment('Payment authorised');
                $order->save();
            } else if($transactionStatus === 'DECLINED') {
                $order->setState("canceled")
                    ->setStatus("canceled")
                    ->addStatusHistoryComment('Payment declined');
                $order->save();
            } else { // error
                $order->setState("canceled")
                    ->setStatus("canceled")
                    ->addStatusHistoryComment('Payment auth error');
                $order->save();
            }
            return $this;
        } else if($result->result === 'redirection') {
            // redirect to the redirection URL
            $merchantId = $result->merchantId;
            $merchantTxId = $result->merchantTxId;
            $txId = $result->txId;
            $redirectionUrl = $result->redirectionUrl;
            Mage::app()->getFrontController()
                ->getResponse()
                ->setRedirect($redirectionUrl);
        } else {
            throw new \Magento\Framework\Validator\Exception(__(json_encode($result->errors)));
        }

        return $this;
    }

    private function getTokenDirectData($apiOperation, $data = array())
    {
        switch ($apiOperation) {

            case "CAPTURE":
            case "REFUND":
                {
                    return $this->getTokenDirectDataCaptureRefund($apiOperation, $data);
                }
            case "VOID":
                {
                    return $this->getTokenDirectDataVoid($apiOperation);
                }
        }
        return null;
    }


    private function getTokenDirectDataCaptureRefund($apiOperation, $data = array()) {
        $payment = $this->getInfoInstance();
        $order = $payment->getOrder();
        // allow origin url
        $allowOriginUrl = $this->_urlBuilder->getBaseUrl();

        // amount
        $amount = $data['amount'];
        // merchant transaction id or order id
        $orderId = $order->getRealOrderId();
        if(strlen($orderId) > 50) {
            $orderId = substr($orderId, -50);
        }

        $sessionTokenData = array(
            "action" => $apiOperation,
            "originalMerchantTxId" => $orderId,
            "amount" => $amount,
            "allowOriginUrl" => $allowOriginUrl

        );

        return $sessionTokenData;
    }

    private function getTokenDirectDataVoid($apiOperation) {
        $payment = $this->getInfoInstance();
        $order = $payment->getOrder();
        // allow origin url
        $allowOriginUrl = $this->_urlBuilder->getBaseUrl();

        // merchant transaction id or order id
        $orderId = $order->getRealOrderId();
        if(strlen($orderId) > 50) {
            $orderId = substr($orderId, -50);
        }

        $sessionTokenData = array(
            "action" => $apiOperation,
            "originalMerchantTxId" => $orderId,
            "allowOriginUrl" => $allowOriginUrl

        );

        return $sessionTokenData;
    }

    private function getAPIParametersForDirect($apiOperation, $data = array())
    {
        switch ($apiOperation) {
            case "AUTH":
            case "PURCHASE":
            case "VERIFY":
                {
                    return $this->getAPIParametersForDirectAuthPurchaseVerify($apiOperation, $data);
                }
            case "CAPTURE":
            case "REFUND":
            case "VOID":
                {
                    return $this->getAPIParametersForDirectCaptureRefundVoid($apiOperation, $data);
                }
        }
        return null;
    }

    private function getAPIParametersForDirectAuthPurchaseVerify($apiOperation, $data = array())
    {
        $merchantId = trim($this->_helper->getConfigData('merchant_id'));
        $data['merchantId'] = $merchantId;
        $paymentMethodID = trim($this->_helper->getConfigData('payment_method'));
        if ($paymentMethodID != '') {
            $data['paymentSolutionId'] = $paymentMethodID;
        }

        return $data;
    }

    private function getAPIParametersForDirectCaptureRefundVoid($apiOperation, $data = array())
    {
        $merchantId = trim($this->_helper->getConfigData('merchant_id'));
        $data['merchantId'] = $merchantId;

        return $data;
    }

    /**
     * Capture payment
     *
     * @param \Magento\Framework\DataObject|InfoInterface $payment
     * @param float $amount
     * @return $this
     * @throws \Magento\Framework\Exception\LocalizedException
     * @api
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     * @deprecated 100.2.0
     */
    public function capture(\Magento\Payment\Model\InfoInterface $payment, $amount)
    {
        parent::capture($payment, $amount);
        $data = array(
            "amount" => $amount
        );
        $sessionTokenData = $this->getTokenDirectData("CAPTURE", $data);

        /*$token = $this->getToken($sessionTokenData);
        if ($token->result != 'success' ) {
            throw new \Magento\Framework\Validator\Exception(__(json_encode($token->errors)));
        }*/
        $params = $this->getAPIParametersForDirect("CAPTURE", $data);
        //$params['token'] = $token->token;
		$params = array_merge($sessionTokenData, $params);
		$result = $this->_helper->executeGatewayTransaction("CAPTURE", $params);
        if($result->result === 'success') {
            $payment->setTransactionId($result->originalMerchantTxId)
                ->setTransactionAdditionalInfo(\Magento\Sales\Model\Order\Payment\Transaction::RAW_DETAILS, json_encode($result));
            $order = $payment->getOrder();
            $transactionStatus = $result->status;
            if ($transactionStatus === 'SET_FOR_CAPTURE') { // capture was successful
                $order->setState("Paid")
                    ->setStatus("processing")
                    ->addStatusHistoryComment(__('Payment captured'));
                $order->save();
            } else { // error
                $order->setState("canceled")
                    ->setStatus("canceled")
                    ->addStatusHistoryComment('Payment capture error');
                $order->save();
            }
            return $this;
        } else if($result->result === 'redirection') {
            // redirect to the redirection URL
            $merchantId = $result->merchantId;
            $merchantTxId = $result->merchantTxId;
            $txId = $result->txId;
            $redirectionUrl = $result->redirectionUrl;
            Mage::app()->getFrontController()
                ->getResponse()
                ->setRedirect($redirectionUrl);
        } else if (strpos($result->errors, 'current status: SUCCESS') !== false) {
            $order = $payment->getOrder();
            $payment->setTransactionId($order->getRealOrderId());
            $order->setState("Paid")
                ->setStatus("processing")
                ->addStatusHistoryComment(__('Payment captured'));
            $order->save();
            return $this;
        } else {
            throw new \Magento\Framework\Validator\Exception(__(json_encode($result->errors)));
        }

        return $this;
    }

    /**
     * Refund specified amount for payment
     *
     * @param \Magento\Framework\DataObject|InfoInterface $payment
     * @param float $amount
     * @return $this
     * @throws \Magento\Framework\Exception\LocalizedException
     * @api
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     * @deprecated 100.2.0
     */
    public function refund(\Magento\Payment\Model\InfoInterface $payment, $amount)
    {
        parent::refund($payment, $amount);

        $data = array(
            "amount" => $amount
        );
        $sessionTokenData = $this->getTokenDirectData("REFUND", $data);
/*
        $token = $this->getToken($sessionTokenData);
        if ($token->result != 'success' ) {
            throw new \Magento\Framework\Validator\Exception(__(json_encode($token->errors)));
        }
*/
        $params = $this->getAPIParametersForDirect("REFUND", $data);
        //$params['token'] = $token->token;
        $params = array_merge($sessionTokenData, $params);
        $result = $this->_helper->executeGatewayTransaction("REFUND", $params);
        if($result->result === 'success') {
            $payment->setTransactionId($result->originalMerchantTxId)
                ->setTransactionAdditionalInfo(\Magento\Sales\Model\Order\Payment\Transaction::RAW_DETAILS, json_encode($result));
            $order = $payment->getOrder();
            $transactionStatus = $result->status;
            if ($transactionStatus === 'SET_FOR_REFUND') { // refund was successful
                $order->setState("processing")
                    ->setStatus("processing")
                    ->addStatusHistoryComment('Payment refunded amount ' . $amount);
                $transaction = $payment->addTransaction(Transaction::TYPE_REFUND, null, true);
                $transaction->setIsClosed(0);
                $transaction->save();
                $order->save();
            } else { // error
                $order->setState("canceled")
                    ->setStatus("canceled")
                    ->addStatusHistoryComment('Payment refund error');
                $order->save();
            }
            return $this;
        } else {
            if (strpos($result->errors, 'Transaction not refundable: Original transaction not SUCCESS') !== false) {
                if($payment->getOrder()->getBaseGrandTotal() == $amount) {
                    return $this->void($payment);
                } else {
                    throw new \Magento\Framework\Validator\Exception(__(json_encode($result->errors)));
                }
            }
            else {
                throw new \Magento\Framework\Validator\Exception(__(json_encode($result->errors)));
            }
        }

        return $this;
    }

    /**
     * Cancel payment
     *
     * @param \Magento\Framework\DataObject|InfoInterface $payment
     * @return $this
     * @api
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     * @deprecated 100.2.0
     */
    public function cancel(\Magento\Payment\Model\InfoInterface $payment)
    {
        return $this->void($payment);
    }

    /**
     * Void payment
     *
     * @param \Magento\Framework\DataObject|InfoInterface $payment
     * @return $this
     * @throws \Magento\Framework\Exception\LocalizedException
     * @api
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     * @deprecated 100.2.0
     */
    public function void(\Magento\Payment\Model\InfoInterface $payment)
    {
        parent::void($payment);

        $sessionTokenData = $this->getTokenDirectData("VOID");
/*
        $token = $this->getToken($sessionTokenData);
        if ($token->result != 'success' ) {
            throw new \Magento\Framework\Validator\Exception(__(json_encode($token->errors)));
        }
*/
        $params = $this->getAPIParametersForDirect("VOID");
        //$params['token'] = $token->token;
        $params = array_merge($sessionTokenData, $params);
        $result = $this->_helper->executeGatewayTransaction("VOID", $params);
        if($result->result === 'success') {
            $payment->setTransactionId($result->originalMerchantTxId)
                ->setTransactionAdditionalInfo(\Magento\Sales\Model\Order\Payment\Transaction::RAW_DETAILS, json_encode($result));
            $order = $payment->getOrder();
            $transactionStatus = $result->status;
            if ($transactionStatus === 'VOID') { // void was successful
                $order->setState("processing")
                    ->setStatus("processing")
                    ->addStatusHistoryComment('Payment voided');
                $transaction = $payment->addTransaction(Transaction::TYPE_VOID, null, true);
                $transaction->setIsClosed(1);
                $transaction->save();
                $order->save();
            } else { // error
                $order->setState("canceled")
                    ->setStatus("canceled")
                    ->addStatusHistoryComment('Payment void error');
                $order->save();
            }
            return $this;
        } else {
            throw new \Magento\Framework\Validator\Exception(__(json_encode($result->errors)));
        }

        return $this;
    }

    /**
     * Retrieve request object.
     *
     * @return \Magento\Framework\App\RequestInterface
     */
    protected function _getRequest()
    {
        return $this->_request;
    }
}
