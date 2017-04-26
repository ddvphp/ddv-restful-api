<?php

  namespace DdvPhp\DdvRestfulApi\Exception;

  use DdvPhp\DdvRestfulApi\Exception\Ddv as DdvException;


  class Error extends DdvException
  {
    // 魔术方法
    public function __construct( $message = 'Unknown Error' , $errorId = 'UNKNOWN_ERROR' , $code = '400', $errorData = array() )
    {
      parent::__construct( $message , $errorId , $code, $errorData );
    }
  }