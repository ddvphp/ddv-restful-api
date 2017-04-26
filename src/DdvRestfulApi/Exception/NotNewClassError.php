<?php

  namespace DdvPhp\DdvRestfulApi\Exception;

  use DdvPhp\DdvRestfulApi\Exception\Error as DdvErrorException;


  class NotNewClassError extends DdvErrorException
  {
    // 魔术方法
    public function __construct( $message = 'This class does not support instantiation', $errorId = 'NotNewClassError' , $code = '500' )
    {
      parent::__construct( $message , $errorId , $code );
    }
  }