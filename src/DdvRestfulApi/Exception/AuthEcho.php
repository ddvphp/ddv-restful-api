<?php

  namespace DdvPhp\DdvRestfulApi\Exception;

  use \DdvPhp\DdvRestfulApi\Exception\RJsonSuccess as RJsonSuccessException;

  class AuthEcho extends RJsonSuccessException
  {
    // 魔术方法
    public function __construct($RJsonData = array() , $message = '', $errorId = 'OK' , $code = '200' )
    {
      parent::__construct($RJsonData, $message , $errorId , $code);
    }
  }