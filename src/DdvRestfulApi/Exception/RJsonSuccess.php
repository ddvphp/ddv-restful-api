<?php

  namespace DdvPhp\DdvRestfulApi\Exception;

  use DdvPhp\DdvRestfulApi\Exception\Ddv as DdvException;


  class RJsonSuccess extends DdvException
  {
    // 魔术方法
    public function __construct($message = '' , $errorId = 'OK' , $code = '200' )
    {
      parent::__construct( $message , $errorId , $code );
    }
  }