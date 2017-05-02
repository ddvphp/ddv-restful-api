<?php

  namespace DdvPhp\DdvRestfulApi\Exception;

  use \DdvPhp\DdvRestfulApi\Exception\Error as DdvErrorException;


  class RequestParseError extends DdvErrorException
  {
    // 魔术方法
    public function __construct( $message = 'Request parse error', $errorId = 'RequestParseError' , $code = '500' )
    {
      parent::__construct( $message , $errorId , $code );
    }
  }