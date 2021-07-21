<?php

namespace EService\Payment\Helper\sdk\lib;

abstract class Executable extends Configurable {

    public abstract function validate();

    public abstract function execute($callback = NULL, $result_from_prev = array());
}
