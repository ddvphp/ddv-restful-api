<?php

namespace DdvPhp\DdvRestfulApi\Exception;

class RJsonSuccess extends \DdvPhp\DdvException\Ddv
{
  // 魔术方法
  public function __construct($RJsonData = array(), $message = '' , $errorId = 'OK' , $code = '200' )
  {
    parent::__construct( $message , $errorId , $code, $RJsonData );
  }
}