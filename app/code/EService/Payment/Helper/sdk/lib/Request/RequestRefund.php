<?php

namespace EService\Payment\Helper\sdk\lib\Request;

use EService\Payment\Helper\sdk\lib\Request;
use EService\Payment\Helper\sdk\lib\Request\Action\RequestActionRefund;
use EService\Payment\Helper\sdk\lib\Request\Token\RequestTokenRefund;

class RequestRefund extends Request {

    public function __construct($values = array()) {
        parent::__construct();
        $this->_token_request = new RequestTokenRefund($values);
        $this->_action_request = new RequestActionRefund($values);
    }

}
