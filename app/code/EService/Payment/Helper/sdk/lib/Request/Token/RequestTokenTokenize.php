<?php

namespace EService\Payment\Helper\sdk\lib\Request\Token;

use EService\Payment\Helper\sdk\lib\Payments;
use EService\Payment\Helper\sdk\lib\Request\RequestToken;

class RequestTokenTokenize extends RequestToken {

    protected $_params = array(
        "action" => array(
            "type" => "mandatory",
            "values" => array(Payments::ACTION_TOKENIZE),
        ),
        "merchantId" => array("type" => "mandatory"),
        "password" => array("type" => "mandatory"),
        "timestamp" => array("type" => "mandatory"),
        "allowOriginUrl" => array("type" => "mandatory"),
        "customerId"  => array("type" => "optional"),
    );

    public function __construct() {
        parent::__construct();
        $this->_data["action"] = Payments::ACTION_TOKENIZE;
    }

}
