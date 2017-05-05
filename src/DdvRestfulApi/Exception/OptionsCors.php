<?php

  namespace DdvPhp\DdvRestfulApi\Exception;

  use \DdvPhp\DdvRestfulApi\Exception\RJsonSuccess as RJsonSuccessException;


  class OptionsCors extends RJsonSuccessException
  {
    // 魔术方法
    public function __construct($message = '' , $errorId = 'OK' , $code = '200' )
    {
      parent::__construct(array(), $message , $errorId , $code );
    }
  }