<?php

namespace DdvPhp\DdvRestfulApi\Exception;

class RJsonError extends \DdvPhp\DdvException\Error
{
  // 魔术方法
  public function __construct( $message = 'Unknown Error', $errorId = 'UNKNOWN_ERROR' , $code = '400', $errorData  = array() )
  {
    parent::__construct( $message , $errorId , $code, $errorData );
  }
}