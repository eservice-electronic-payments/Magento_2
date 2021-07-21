<?php

namespace EService\Payment\Helper\sdk\lib\Request;

use EService\Payment\Helper\sdk\lib\Request\Action\RequestActionVoid;
use EService\Payment\Helper\sdk\lib\Request\Token\RequestTokenVoid;
use EService\Payment\Helper\sdk\lib\Request\RequestRefund;

class RequestVoid extends RequestRefund {

    public function __construct($values = array()) {
        parent::__construct();
        $this->_token_request = new RequestTokenVoid($values);
        $this->_action_request = new RequestActionVoid($values);
    }

}
