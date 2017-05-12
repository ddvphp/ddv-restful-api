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
    protected $regSessionCard = '/^([\da-f]{4}-[\da-f]{8}-[\da-f]{4}-[\da-f]{4}-[\da-f]{4}-[\da-f]{12}-[\da-f]{8})$/i';
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
      // 读取数据
      $res = $this->authDataDriverObj->read($sessionId);
      // 反序列化并且返回
      return empty($res) ? null : unserialize($res);
    }
    protected function saveAuthData($sessionId, $data = null)
    {
      // 序列化数组
      $res = serialize($data);
      // 保存数据
      $res = $this->authDataDriverObj->write($sessionId, $res);
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
    //判断session_card
    public function isSessionCard($card_id='') {
      return (boolean)preg_match($this->regSessionCard, $card_id);
    }
    //生成session_card
    public function createSessionCard() {
      $ua = isset($_SERVER['HTTP_USER_AGENT'])? $_SERVER['HTTP_USER_AGENT'] : microtime();
      $session_card = strtolower( substr(md5(uniqid(mt_rand(), true)),15,4).'-'.$this->createGuid().'-'.substr(md5($ua),15,8) );
      $session_card = str_replace(substr($session_card,13,6),'-5555-',$session_card);
      return $session_card;
    }
    //生成guid
    public function createGuid() {
      $charid = strtolower(md5(uniqid(mt_rand(), true)));
      $hyphen = chr(45);// "-"
      $uuid = substr($charid, 0, 8).$hyphen.substr($charid, 8, 4).$hyphen.substr($charid,12, 4).$hyphen.substr($charid,16, 4).$hyphen.substr($charid,20,12);
      return $uuid;
    }
    //生成session_key
    public function createSessionKey($session_card=null) {
      $session_card = empty($session_card) ? $this->createSessionCard() : $session_card;
      $ua = isset($_SERVER['HTTP_USER_AGENT'])? $_SERVER['HTTP_USER_AGENT'] : microtime();
      $session_key = strtolower( 
          substr(md5($this->createSessionCard().$session_card),7,4)
          .'-'.substr(md5($this->createSessionCard().mt_rand().$session_card.$this->createGuid()),7,12)
          .'-'.substr(md5(uniqid(mt_rand(), true)),15,4).'-'.$this->createGuid().'-'.substr(md5($ua),15,8)
          .'-'.substr(md5(uniqid(mt_rand(), true).$session_card),7,12)
          .'-'.substr(md5(mt_rand().$session_card),7,4)
          .'-'.substr(md5($session_card.mt_rand().$session_card),7,8)
          .'-'.substr(md5($ua.mt_rand().$ua.$session_card),7,4)
        );
      return $session_key;
    }
  }

 ?>