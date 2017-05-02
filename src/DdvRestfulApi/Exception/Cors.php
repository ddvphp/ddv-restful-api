<?php

  namespace DdvPhp\DdvRestfulApi\Exception;

  use \DdvPhp\DdvRestfulApi\Exception\Error as DdvErrorException;


  class Cors extends DdvErrorException
  {
    // 魔术方法
    public function __construct( $message = 'Cors Error', $errorId = 'CORS_ERROR' , $code = '403' )
    {
      parent::__construct( $message , $errorId , $code );
    }
  }