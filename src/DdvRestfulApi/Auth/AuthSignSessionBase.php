<?php 
  namespace DdvPhp\DdvRestfulApi\Auth;
  use \DdvPhp\DdvRestfulApi\Util\Sign as DdvSign;
  use \DdvPhp\DdvRestfulApi\Exception\AuthError as AuthErrorException;

  /**
  * 
  */
  class AuthSignSessionBase
  {

    protected $authDataDriverObj = null;
    protected $authorization = null;
    protected $signInfo = null;
    public function __construct(&$authorization, &$signInfo, &$config, &$authDataDriver)
    {

      $this->authDataDriverObj = new $authDataDriver();
      // 打开连接
      $this->authDataDriverObj->open($config['authDataDriverConfig']);

      $this->method = strtoupper(empty($_SERVER['REQUEST_METHOD'])? 'GET' : $_SERVER['REQUEST_METHOD']);
      $this->authorization = trim($authorization) ;
      $this->signInfo = &$signInfo ;
      $this->config = &$config ;
      $this->authDataDriver = &$authDataDriver ;
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
      // 打开连接
      $res = $this->authDataDriverObj->read($sessionId);
      return unserialize($res);
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