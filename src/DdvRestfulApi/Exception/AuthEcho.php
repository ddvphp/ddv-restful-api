<?php

namespace DdvPhp\DdvRestfulApi\Exception;

class AuthEcho extends \DdvPhp\DdvRestfulApi\Exception\RJsonSuccess
{
  // 魔术方法
  public function __construct($RJsonData = array() , $message = '', $errorId = 'OK' , $code = '200' )
  {
    parent::__construct($RJsonData, $message , $errorId , $code);
  }
}