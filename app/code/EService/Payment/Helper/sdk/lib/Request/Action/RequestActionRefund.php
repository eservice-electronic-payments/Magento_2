<?php

namespace EService\Payment\Helper\sdk\lib\Request\Action;

use EService\Payment\Helper\sdk\lib\Payments;
use EService\Payment\Helper\sdk\lib\Request\RequestAction;

class RequestActionRefund extends RequestAction {

    protected $_params = array(
        "merchantId" => array("type" => "mandatory"),
        "token" => array("type" => "mandatory"),
    );

    public function __construct() {
        parent::__construct();
        $this->_data["action"] = Payments::ACTION_REFUND;
    }

}
