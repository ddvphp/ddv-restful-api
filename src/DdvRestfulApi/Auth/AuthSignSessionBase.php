<?php 
  namespace DdvPhp\DdvRestfulApi\Auth;
  use \DdvPhp\DdvRestfulApi\Sign as DdvSign;
  use \DdvPhp\DdvRestfulApi\Exception\AuthError as AuthErrorException;

  /**
  * 
  */
  class AuthSignSessionBase
  {

    protected $authorization = null;
    protected $signInfo = null;
    public function __construct($authorization = null, $signInfo = null)
    {
      $this->method = strtoupper(empty($_SERVER['REQUEST_METHOD'])? 'GET' : $_SERVER['REQUEST_METHOD']);
      $this->authorization = trim($authorization) ;
      $this->signInfo = $signInfo ;
      $this->authVersion = (isset($signInfo['authVersion']) && is_string($signInfo['authVersion'])) ? $signInfo['authVersion'] : '' ;
      $this->signBaseHeaders = (isset($signInfo['header']) && is_array($signInfo['header'])) ? $signInfo['header'] : array() ;
      $this->signBaseHeadersSys = (isset($this->signBaseHeaders['sys']) && is_array($this->signBaseHeaders['sys'])) ? $this->signBaseHeaders['sys'] : array() ;
      $this->signBaseHeadersX = (isset($this->signBaseHeaders['x']) && is_array($this->signBaseHeaders['x'])) ? $this->signBaseHeaders['x'] : array() ;
      $this->signBaseHeadersPrefix = (isset($this->signBaseHeaders['headersPrefix']) && is_string($this->signBaseHeaders['headersPrefix'])) ? $this->signBaseHeaders['headersPrefix'] : 'x-ddv-' ;
      // 检测基本数据
      $this->checkBaseData();
    }
    protected function checkBaseData($authorization = null, $signInfo = null)
    {
      if (!$this->authorization) {
        throw new AuthErrorException('Authentication Empty Error', 'AUTHENTICATION_EMPTY_ERROR', '403');
      }
      if (!$this->signInfo) {
        throw new AuthErrorException('Authentication Sign Info Empty Error', 'AUTHENTICATION_SIGN_INFO_EMPTY_ERROR', '403');
      }
    }
    protected function getAuthData($sessionId)
    {
      return array(
        'card'=>'ed9a-d251b2e6-48c3-9c08-e426-ed15398ac305-73624bb2',
        'key'=>'c4ba-ae8878c1641b-270a-073bb98e-cc54-1590-2a48-79a304e5a6cb-9dda07f2-1d03eef14b56-29d0-5a14db07-abf6'
      );
    }

    //在uri编码中不能对'/'编码
    public function runSign()
    {
      return $this->sign();
    }

    //在uri编码中不能对'/'编码
    public function urlEncodeExceptSlash($path)
    {
      return DdvSign::urlEncodeExceptSlash($path);
    }

    //使用编码数组编码
    public function urlEncode($value)
    {
      return DdvSign::urlEncode($value);
    }
    //使用编码数组编码
    public function urlDecode($value)
    {
      return DdvSign::urlDecode($value);
    }
  }

 ?>