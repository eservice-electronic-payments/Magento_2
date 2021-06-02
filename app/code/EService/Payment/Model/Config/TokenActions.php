<?php

namespace EService\Payment\Model\Config;

class TokenActions
{
    const TOKEN_ACTION_AUTH = 'AUTH';
    const TOKEN_ACTION_PURCHASE = 'PURCHASE';
    const TOKEN_ACTION_CAPTURE = 'CAPTURE';
    const TOKEN_ACTION_VOID = 'VOID';
    const TOKEN_ACTION_REFUND = 'REFUND';
    const TOKEN_ACTION_GET_STATUS = 'GET_STATUS';
    const TOKEN_ACTION_GET_AVAILABLE_PAYSOLS = 'GET_AVAILABLE_PAYSOLS';
}
