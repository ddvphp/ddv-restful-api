<?php

  namespace DdvPhp\DdvRestfulApi\Exception;

  use Exception;


  class Ddv extends Exception
  {
    /* 属性 */
    protected $errorId ;
    protected $responseData ;
    // 魔术方法
    public function __construct( $message = '' , $errorId = 'UNKNOWN_ERROR' , $code = '500', $responseData = array() )
    {
      parent::__construct($message,$code);
      empty($errorId)||$this->errorId = $errorId;
      empty($responseData)||$this->responseData = $responseData;
    }
    public function getErrorId(){
      $errorId = empty($this->errorId)?'UNKNOWN_ERROR':$this->errorId;
      return $errorId;
    }
    public function getResponseData(){
      return empty($this->responseData)?array():$this->responseData;
    }

  }