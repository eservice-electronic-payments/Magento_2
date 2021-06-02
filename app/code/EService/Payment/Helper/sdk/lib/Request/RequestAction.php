<?php

namespace EService\Payment\Helper\sdk\lib\Request;

use EService\Payment\Helper\sdk\lib\Config;
use EService\Payment\Helper\sdk\lib\Request;

class RequestAction extends Request
{

    public function execute($callback = null, $result_from_prev = array())
    {
        foreach ($result_from_prev as $k => $v) {
            $this->_data[$k] = $v;
        }
        $data = $this->validate();
        return $this->_exec_post(Config::$PaymentOperationActionUrl, $data, $callback);
    }

    public function _get_data_from_prev($result_from_prev)
    {
        $this->_data["merchantId"] = $result_from_prev["merchantId"];
        $this->_data["token"] = $result_from_prev["token"];
    }
}
