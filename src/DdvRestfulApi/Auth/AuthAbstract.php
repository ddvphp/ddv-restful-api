<?php 
namespace DdvPhp\DdvRestfulApi\Auth;
use DdvPhp\DdvAuth\Sign;
use \DdvPhp\DdvUrl as DdvUrl;
use \DdvPhp\DdvRestfulApi\Util\Auth as DdvAtuh;
use \DdvPhp\DdvRestfulApi\Exception\AuthError as AuthErrorException;

/**
* 
*/
abstract class AuthAbstract
{

  protected $authDatas = array();
  protected $authDataDriverObj = null;
  protected $authorization = null;
  protected $signInfo = null;
  protected $regSessionCard = '/^([\da-f]{4}-[\da-f]{8}-[\da-f]{4}-[\da-f]{4}-[\da-f]{4}-[\da-f]{12}-[\da-f]{8})$/i';
  public function __destruct()
  {
    $this->authDataDriverClose();
  }
  public function __construct(&$authorization, &$signInfo, &$config)
  {


    $this->method = strtoupper(empty($_SERVER['REQUEST_METHOD'])? 'GET' : $_SERVER['REQUEST_METHOD']);
    $this->authorization = trim($authorization) ;
    $this->signInfo = &$signInfo ;
    $this->config = &$config ;

    if (!empty($config['authDataDriver'])) {
      $this->initAuthData($config['authDataDriver']) ;
    }
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

  //在uri编码中不能对'/'编码
  public function runSign()
  {
    try {
      return $this->sign();
    } catch (Exception $e) {
      $this->_sysClose();
      throw new $e;
    }
  }

  //判断session_card
  public function isSessionCard($card_id='') {
    return (boolean)preg_match($this->regSessionCard, $card_id);
  }
  //生成session_card
  public function createSessionCard() {
    $ua = isset($_SERVER['HTTP_USER_AGENT'])? $_SERVER['HTTP_USER_AGENT'] : microtime();
    $session_card = strtolower( substr(md5(uniqid(mt_rand(), true)),15,4).'-'.Sign::createGuid().'-'.substr(md5($ua),15,8) );
    $session_card = str_replace(substr($session_card,13,6),'-5555-',$session_card);
    return $session_card;
  }
  //生成session_key
  public function createSessionKey($session_card=null) {
    $session_card = empty($session_card) ? $this->createSessionCard() : $session_card;
    $ua = isset($_SERVER['HTTP_USER_AGENT'])? $_SERVER['HTTP_USER_AGENT'] : microtime();
    $session_key = strtolower( 
        substr(md5($this->createSessionCard().$session_card),7,4)
        .'-'.substr(md5($this->createSessionCard().mt_rand().$session_card.Sign::createGuid()),7,12)
        .'-'.substr(md5(uniqid(mt_rand(), true)),15,4).'-'.Sign::createGuid().'-'.substr(md5($ua),15,8)
        .'-'.substr(md5(uniqid(mt_rand(), true).$session_card),7,12)
        .'-'.substr(md5(mt_rand().$session_card),7,4)
        .'-'.substr(md5($session_card.mt_rand().$session_card),7,8)
        .'-'.substr(md5($ua.mt_rand().$ua.$session_card),7,4)
      );
    return $session_key;
  }

  public function generateSessionId(){
    if ($this->authDataDriverObj && method_exists($this->authDataDriverObj, 'generateSessionId')) {
      return $this->authDataDriverObj->generateSessionId();
    }
    $sidLength = @ini_get('session.sid_length');
    $sidLength = !isset($sidLength) || intval($sidLength) <= 8 ? 32 : $sidLength;
    $randomSid = bin2hex(random_bytes($sidLength));
    // Use same charset as PHP
    $sessionId = '';
    if (!$sidLength>12) {
      $pp = isset($_SERVER['REMOTE_ADDR'])? $_SERVER['REMOTE_ADDR'] : microtime();
      $pp .= isset($_SERVER['HTTP_CLIENT_IP'])? $_SERVER['HTTP_CLIENT_IP'] : microtime();
      $pp .= isset($_SERVER['HTTP_X_FORWARDED_FOR'])? $_SERVER['HTTP_X_FORWARDED_FOR'] : microtime();
      $sessionId = substr(md5(microtime().$pp.mt_rand().Sign::createGuid()), 0, 3);
      $sessionId .= substr(md5($pp.microtime().mt_rand().$sidLength.$randomSid), 0, 3);
      $sessionId .= substr(md5(microtime().$pp.mt_rand().$pp.$this->createSessionKey().$sessionId), 0, 3);
      $sessionId .= substr(md5(microtime().$sessionId.mt_rand().$pp.$randomSid), 0, 3);
    }
    $sessionId .= substr(rtrim(strtr($randomSid, '+/', ',-'), '='), 0, $sidLength-($sidLength>12?12:0));
    $sessionId = substr($sessionId, 0, $sidLength);

    if ($this->getAuthData($sessionId)!==null) {
      return $this->generateSessionId();
    }
    return $sessionId;
  }
  protected function initAuthData($authDataDriverInput)
  {
    // 默认是这种模式查找
    $authDataDriver = $authDataDriverInput;
    if (!class_exists($authDataDriver)) {
      $authDataDriver = '\\DdvPhp\\DdvRestfulApi\\AuthData\\AuthData'.ucfirst($authDataDriverInput).'Driver';
    }
    if (!class_exists($authDataDriver)) {
      $authDataDriver = '\\'.$authDataDriverInput;
    }
    if (!class_exists($authDataDriver)) {
      throw new AuthErrorException('authDataDriver Class Not Find', 'AUTHDATADRIVER_CLASS_NOT_FIND', '500');
    }

    $this->authDataDriver = &$authDataDriver ;
  }
  public function getAuthData($sessionId)
  {
    if (isset($authDatas[$sessionId])) {
      return $authDatas[$sessionId];
    }
    // 读取数据
    $res = $this->authDataDriverObj()->read($sessionId);
    // 反序列化并且返回
    $authDatas[$sessionId] = empty($res) ? null : unserialize($res);
    return $authDatas[$sessionId];
  }
  public function saveAuthData($sessionId, $data = null)
  {
    $authDatas[$sessionId] = $data;
    // 序列化数组
    $res = serialize($data);
    // 保存数据
    $res = $this->authDataDriverObj()->write($sessionId, $res);
  }

  public function authDataDriverObj(){
    if ($this->authDataDriverObj && method_exists($this->authDataDriverObj, 'close')) {
      return $this->authDataDriverObj;
    }
    $this->authDataDriverObj = new $this->authDataDriver();
    // 打开连接
    $this->authDataDriverObj->open($this->config['authDataDriverConfig']);
    return $this->authDataDriverObj;
  }
  public function authDataDriverClose(){
    if (!($this->authDataDriverObj && method_exists($this->authDataDriverObj, 'close'))) {
      return;
    }
    // 打开连接
    $this->authDataDriverObj->close();
    $this->authDataDriverObj = null;
  }
  public function getSignHeaders($signHeaderKeys = array())
  {
    $signHeaderKeysLen = count($signHeaderKeys);
    //把临时授权键名的-替换为_ 因为php的特殊原因
    $headerKeyPrefix = str_replace('-','_',$this->signBaseHeadersPrefix);
    $headerKeyPrefixLen = strlen($headerKeyPrefix);
    $signBaseHeadersSys = is_array($this->signBaseHeadersSys)?$this->signBaseHeadersSys:array();
    $signBaseHeadersX = is_array($this->signBaseHeadersX)?$this->signBaseHeadersX:array();
    $signHeaders = array();
    for ($i=0; $i < $signHeaderKeysLen; $i++) {
      //把临时授权键名的-替换为_ 因为php的特殊原因
      $authHeaderKey = strtolower(str_replace('-','_',$signHeaderKeys[$i]?$signHeaderKeys[$i]:''));
      //判断是否符合自定义头
      if (substr($authHeaderKey, 0, $headerKeyPrefixLen) == $headerKeyPrefix) {

        //试图获取自定义头
        if (!isset($signBaseHeadersX[$authHeaderKey])) {
          //自定义头获取失败，抛出异常
          throw new AuthErrorException('I did not find your authorization header['.$signHeaderKeys[$i].']','AUTHORIZATION_HEADERS_X_NOT_FIND',403);
        }
        $signHeaders[$signHeaderKeys[$i]] = $signBaseHeadersX[$authHeaderKey];
        unset($signBaseHeadersX[$authHeaderKey]);
      }else{
        if (isset($signBaseHeadersSys[$authHeaderKey])) {
          $signHeaders[$signHeaderKeys[$i]] = $signBaseHeadersSys[$authHeaderKey];
          unset($signBaseHeadersSys[$authHeaderKey]);
        }else{
          //如果没法直接获取就加http_试试，因为php的特殊性
          $authHeaderKey = 'HTTP_'.strtoupper($authHeaderKey);
          if(isset($signBaseHeadersSys[$signHeaderKeys[$i]])){
            $signHeaders[$signHeaderKeys[$i]] = $signBaseHeadersSys[$signHeaderKeys[$i]];
            unset($signBaseHeadersSys[$signHeaderKeys[$i]]);
          }else if (isset($_SERVER[$authHeaderKey])) {
            $signHeaders[$signHeaderKeys[$i]] = $_SERVER[$authHeaderKey];
            unset($signBaseHeadersSys[$authHeaderKey]);
          }else if(empty($signHeaderKeys[$i])){
            unset($signBaseHeadersSys[$signHeaderKeys[$i]]);
            unset($signHeaderKeys[$i]);
          }else{
            throw new AuthErrorException('I did not find your authorization header['.$signHeaderKeys[$i].']','AUTHORIZATION_HEADERS_S_NOT_FIND',403);
          }
        }
      }
    }
    //检测是否还有没有签名的自定义头
    if (!empty($signBaseHeadersX)) {
      throw new AuthErrorException('The following header information you have not authenticated['. implode(',',$signBaseHeadersX).']','AUTHORIZATION_HEADERS_X_NOT_ALL_SIGNATURES',403);
    }
    if (isset($signBaseHeadersSys['content-length'])&&intval($signBaseHeadersSys['content-length'])==0) {
      unset($signBaseHeadersSys['content-md5'], $signBaseHeadersSys['content-type'], $signBaseHeadersSys['content-length']);
    }
    //检测是否还有没有签名的系统头
    if (!empty($signBaseHeadersX)) {
      throw new AuthErrorException('The following header information you have not authenticated[content_md5 or content_type or content_length]','AUTHORIZATION_HEADERS_S_NOT_ALL_SIGNATURES',403);
    }
    return $signHeaders;
  }
  public function checkContentMd5True(){
    if($this->signInfo['isContentLengthTrue']!==true){
      throw new AuthErrorException('Content Length Error','CONTENT_LENGTH_ERROR',403);
    }
  }
  public function checkContentLengthTrue(){
    if($this->signInfo['isContentLengthTrue']!==true){
      throw new AuthErrorException('Content Length Error','CONTENT_LENGTH_ERROR',403);
    }
  }
}
