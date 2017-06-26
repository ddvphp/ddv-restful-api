<?php

namespace DdvPhp\DdvRestfulApi\Exception;

class RequestParseError extends \DdvPhp\DdvRestfulApi\Exception\RJsonError
{
  // 魔术方法
  public function __construct( $message = 'Request parse error', $errorId = 'RequestParseError' , $code = '500' )
  {
    parent::__construct( $message , $errorId , $code );
  }
}