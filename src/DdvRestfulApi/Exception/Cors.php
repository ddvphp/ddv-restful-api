<?php

namespace DdvPhp\DdvRestfulApi\Exception;

class Cors extends \DdvPhp\DdvRestfulApi\Exception\RJsonError
{
  // 魔术方法
  public function __construct( $message = 'Cors Error', $errorId = 'CORS_ERROR' , $code = '403' )
  {
    parent::__construct( $message , $errorId , $code );
  }
}