<?php

  namespace DdvPhp\DdvRestfulApi\Exception;

  use \DdvPhp\DdvRestfulApi\Exception\Error as DdvErrorException;


  class AuthError extends DdvErrorException
  {
    // 魔术方法
    public function __construct( $message = 'Authentication Error' , $errorId = 'AUTHENTICATION_ERROR' , $code = '403', $errorData = array() )
    {
      parent::__construct( $message , $errorId , $code, $errorData );
    }
  }