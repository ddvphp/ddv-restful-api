<?php 
  namespace DdvPhp\DdvRestfulApi\Auth;
  use \DdvPhp\DdvRestfulApi\Exception\AuthError as AuthErrorException;
  use \DdvPhp\DdvRestfulApi\Exception\AuthEcho as AuthEchoException;
  /**
  * 
  */
  class AuthSignSessionInitV1 extends \DdvPhp\DdvRestfulApi\Auth\AuthSignSessionBase
  {
    private $regAuth = 
      '/^([\da-f]{8}-[\da-f]{4}-[\da-f]{4}-[\da-f]{4}-[\da-f]{12})\/([0-9a-zA-Z,-]+)\/([\da-f]{4}-[\da-f]{8}-[\da-f]{4}-[\da-f]{4}-[\da-f]{4}-[\da-f]{12}-[\da-f]{8})\/([\d]{4}-[\d]{2}-[\d]{2}T[\d]{2}:[\d]{2}:[\d]{2}Z)\/([\d]+)\/([\da-f]{8}-[\da-f]{4}-[\da-f]{4}-[\da-f]{4}-[\da-f]{12})\/([\da-f]{64})$/i';
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
        var_dump('不通过');
      }
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