<?php

namespace DdvPhp\DdvRestfulApi\Exception;

class AuthError extends RJsonError
{
    // 魔术方法
    public function __construct($message = 'Authentication Error', $errorId = 'AUTHENTICATION_ERROR', $code = '403', $errorData = array())
    {
        parent::__construct($message, $errorId, $code, $errorData);
    }
}