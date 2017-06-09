<?php

namespace DdvPhp\DdvRestfulApi\Exception;

class OptionsCors extends \DdvPhp\DdvRestfulApi\Exception\RJsonSuccess
{
  // 魔术方法
  public function __construct($message = '' , $errorId = 'OK' , $code = '200' )
  {
    parent::__construct(array(), $message , $errorId , $code );
  }
}