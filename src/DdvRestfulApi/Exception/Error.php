<?php

  namespace DdvPhp\DdvRestfulApi\Exception;

  use DdvPhp\DdvRestfulApi\Exception\Ddv as DdvException;


  class Error extends DdvException
  {
    // 魔术方法
    public function __construct( $message = 'unknown error' , $errorId = 'unknown_error' , $code = '400', $errorData = array() )
    {
      parent::__construct( $message , $errorId , $code, $errorData );
    }
  }