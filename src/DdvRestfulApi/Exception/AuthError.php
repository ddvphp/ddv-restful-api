<?php

  namespace DdvPhp\DdvRestfulApi\Exception;

  use DdvPhp\DdvRestfulApi\Exception\Error as DdvErrorException;


  class AuthError extends DdvErrorException
  {
    // 魔术方法
    public function __construct( $message = 'unknown error' , $errorId = 'unknown_error' , $code = '400', $errorData = array() )
    {
      parent::__construct( $message , $errorId , $code, $errorData );
    }
  }