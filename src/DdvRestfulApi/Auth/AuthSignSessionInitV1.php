<?php
  namespace DdvPhp\DdvRestfulApi\Auth;
  use \DdvPhp\DdvRestfulApi\Exception\AuthError as AuthErrorException;
  use \DdvPhp\DdvRestfulApi\Exception\AuthEcho as AuthEchoException;
  /**
  *
  */
  class AuthSignSessionInitV1 extends AuthAbstract
  {
    private static $accessKeyId = null;
    private static $sessionCard = null;
    private static $requestId = null;
    private $regAuth =
      '/^([\da-f]{8}-[\da-f]{4}-[\da-f]{4}-[\da-f]{4}-[\da-f]{12})\/([0-9a-zA-Z,-]+)\/([\da-f]{4}-[\da-f]{8}-[\da-f]{4}-[\da-f]{4}-[\da-f]{4}-[\da-f]{12}-[\da-f]{8})\/([\d]{4}-[\d]{2}-[\d]{2}T[\d]{2}:[\d]{2}:[\d]{2}Z)\/([\d]+)\/([\da-f]{8}-[\da-f]{4}-[\da-f]{4}-[\da-f]{4}-[\da-f]{12})\/([\da-f]{64})$/i';
    private $regSessionId = '/^([0-9a-zA-Z,-]+)$/i';
    private $regRequestId = '/^([\da-f]{8}-[\da-f]{4}-[\da-f]{4}-[\da-f]{4}-[\da-f]{12})$/i';
    // 判断是否通过该授权通过的
    public static function is(){
        return !empty(self::$accessKeyId);
    }
    public static function getAccessKeyId(){
        return self::$accessKeyId;
    }
    public static function getSessionId(){
        return self::$accessKeyId;
    }
    public static function getRequestId(){
        return self::$requestId;
    }
    public static function getSessionCard(){
        return self::$sessionCard;
    }
    protected function sign()
    {
      try {
        // 试图旧授权信息
        $this->checkAuth();
        // 输出数据
        throw new AuthEchoException(array(
          // 通过
          'state' => true,
          // ok代表不需要更新客户端的会话数据，不回传密匙同时可以防止密匙泄漏
          'type' => 'ok',
          // 给客户端计算时差
          'serverTime' => time()
        ));
      } catch (AuthErrorException $e) {
        throw new AuthEchoException($this->createAuthData());
      }
    }
    private function createAuthData()
    {
      list(
        // 请求id
        $requestId,
        // 会话id
        $sessionId,
        // 设备id
        $sessionCard,
        // 签名时间
        $signTimeString,
        // 过期时间
        $expiredTimeOffset,
        // 干扰码
        $interference,
        // 客户端签名
        $clientSign
      ) = explode('/', trim($this->authorization));

      //检查请求id
      if (empty($requestId) || !preg_match($this->regRequestId, $requestId)) {
        throw new AuthErrorException('Authentication session_request_id format Error','AUTHORIZATION_ERROR_FORMAT_REQUEST_ID',403);
      }
      //检查card_id
      if (empty($sessionCard) || !$this->isSessionCard($sessionCard)) {
        $sessionCard = $this->createSessionCard();
      }
      //检查sessionId
      if ((!empty($sessionId)) && preg_match($this->regSessionId, $sessionId)) {
        // 授权数据
        $data = $this->getAuthData($sessionId);
      }else{
        $sessionId = $this->generateSessionId();
      }
      $data = isset($data) && is_array($data) ? $data : array();

      if (empty($data['card'])||$sessionCard!==$data['card']) {
        $sessionId = $this->generateSessionId();
        $data['card'] = $sessionCard;
        $data['key'] = $this->createSessionKey($data['card']);
      }
      $data['key'] = empty($data['key']) ? $this->createSessionKey($data['card']) : $data['key'];
      $this->getAuthData($sessionId);
      $this->saveAuthData($sessionId, $data);
      self::$requestId = $requestId;
      self::$accessKeyId = $sessionId;
      self::$sessionCard = $data['card'];
      return array(
        // 通过
        'state' => true,
        // ok代表不需要更新客户端的会话数据，不回传密匙同时可以防止密匙泄漏
        'type' => 'update',
        // 给客户端计算时差
        'serverTime' => time(),
        'sessionData' => array(
          'sessionPrefix' => $this->signBaseHeadersPrefix,
          'sessionId' => $sessionId,
          'sessionCard' => $data['card'],
          'sessionKey' => $data['key'],
          'serverTime' => (string)time()
        )
      );
    }
    private function checkAuth()
    {
      list(
        // 请求id
        $requestId,
        // 会话id
        $sessionId,
        // 设备id
        $sessionCard,
        // 签名时间
        $signTimeString,
        // 过期时间
        $expiredTimeOffset,
        // 客户端签名
        $clientSign,
        // 干扰码
        $interference
      ) = $this->parseAuth();
      // 授权数据
      $data = $this->getAuthData($sessionId);
      if (empty($data)) {
        throw new AuthErrorException('Auth data does not exist!', 'AUTH_DATA_DOES_NOT_EXIST', 403);
      }

      if ($sessionCard!==$data['card']) {
        throw new AuthErrorException('session card Error!', 'AUTHORIZATION_SESSION_CARD_NOT_SELF', 403);
      }
      //授权字符串
      $authString = "{$this->authVersion}/{$requestId}/{$sessionId}/{$sessionCard}/{$signTimeString}/{$expiredTimeOffset}";
      //生成加密key
      $signingKey = hash_hmac('sha256', $authString, $data['key']);

      //拼接干扰码
      $authString = $authString .'/'. $interference ;

      //生成签名
      $serverSign = hash_hmac('sha256', $authString, $signingKey);

      if ($clientSign!==$serverSign) {
        throw new AuthErrorException('session sign Error!', 'AUTHORIZATION_SESSION_SIGN_ERROR', 403);
      }

    }
    private function parseAuth()
    {
      $auths = array();
      // 试图正则匹配
      preg_match($this->regAuth, $this->authorization,$auths);
      //
      if (count($auths)!==8) {
        throw new AuthErrorException('Authentication Info Length Error', 'AUTHORIZATION_ERROR_INFO_LENGTH', 403);
      }elseif (empty($auths[0])) {
        //抛出授权信息格式异常
        throw new AuthErrorException('Authentication wrong format as content','AUTHORIZATION_ERROR_FORMAT_WRONG',403);
      }
      list(
        ,
        // 请求id
        $requestId,
        // 会话id
        $sessionId,
        // 设备id
        $sessionCard,
        // 签名时间
        $signTimeString,
        // 过期时间
        $expiredTimeOffset,
        // 干扰码
        $interference,
        // 客户端签名
        $clientSign
      ) = $auths;
      //签名时间
      $signTime = empty($signTimeString) ? 0 : strtotime(strtoupper($signTimeString));
      //过期
      $expiredTimeOffset = empty($expiredTimeOffset) ? 0 : intval($expiredTimeOffset);
      //签名过期时间
      $expiredTime = $signTime + $expiredTimeOffset;

      if (time()>$expiredTime) {
        //抛出过期
        throw new AuthErrorException('Request authorization expired!', 'AUTHORIZATION_REQUEST_EXPIRED', 403);
      }elseif (($signTime + $expiredTimeOffset) < time()) {
        //签名期限还没有到
        throw new AuthErrorException('Request authorization has not yet entered into force!', 'AUTHORIZATION_REQUEST_NOT_ENABLE', 403);
      }
      return array(
        // 请求id
        $requestId,
        // 会话id
        $sessionId,
        // 设备id
        $sessionCard,
        // 签名时间
        $signTimeString,
        // 过期时间
        $expiredTimeOffset,
        // 客户端签名
        $clientSign,
        // 干扰码
        $interference
      );
    }
  }
?>