<?php

namespace EService\Payment\Helper\sdk\lib\Response;

use EService\Payment\Helper\sdk\lib\Response;
use EService\Payment\Helper\sdk\lib\Response\Error\ResponseErrorErrors;

class ResponseError extends Response {

    protected $_errors = array();

    public function __construct($response, $errors) {
        parent::__construct($response);
        $this->_errors = new ResponseErrorErrors($errors);
    }

    public function get_error($name = null) {
        if (!is_null($name)) {
            if (isset($this->errors->{$name})) {
                return $this->errors->{$name};
            }
            return NULL;
        }
        return $this->errors;
    }

}
