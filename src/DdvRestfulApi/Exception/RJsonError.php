<?php

  namespace DdvPhp\DdvRestfulApi\Exception;

  use \DdvPhp\DdvRestfulApi\Exception\Error as DdvErrorException;


  class RJsonError extends DdvErrorException
  {
    // 魔术方法
    public function __construct( $message = 'Unknown Error', $errorId = 'UNKNOWN_ERROR' , $code = '400' )
    {
      parent::__construct( $message , $errorId , $code );
    }
  }