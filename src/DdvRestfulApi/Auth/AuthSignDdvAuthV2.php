<?php 
  namespace DdvPhp\DdvRestfulApi\Auth;
  use \DdvPhp\DdvRestfulApi\Exception\AuthError as AuthErrorException;
  /**
  * 
  */
  class AuthSignDdvAuthV2 extends \DdvPhp\DdvRestfulApi\Auth\AuthAbstract
  {
    private $regAuth = 
      '/^([\da-f]{8}-[\da-f]{4}-[\da-f]{4}-[\da-f]{4}-[\da-f]{12})\/([0-9a-zA-Z,-]+)\/([\da-f]{4}-[\da-f]{8}-[\da-f]{4}-[\da-f]{4}-[\da-f]{4}-[\da-f]{12}-[\da-f]{8})\/([\d]{4}-[\d]{2}-[\d]{2}T[\d]{2}:[\d]{2}:[\d]{2}Z)\/(\d+)\/([\w\-\;]+)\/([\da-f]{64})$/i';
    protected function sign()
    {
      // 试图旧授权信息
      $this->checkAuth();
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
        // 需要签名的头的key
        $signHeaderKeysStr
      ) = $this->parseAuth();
      $signHeaderKeys = $this->getSignHeaderKeysByStr($signHeaderKeysStr);
      // 授权数据
      $data = $this->getAuthData($sessionId);

      if ($sessionCard!==$data['card']) {
        throw new AuthErrorException('session card Error!','AUTHORIZATION_SESSION_CARD_NOT_SELF',403);
      }
      // 通过
      $signHeaders = $this->getSignHeaders($signHeaderKeys);
      // 授权字符串
      $authString = "{$this->authVersion}/{$requestId}/{$sessionId}/{$sessionCard}/{$signTimeString}/{$expiredTimeOffset}";
      //生成加密key
      $signingKey = hash_hmac('sha256', $authString, $data['key']);

      //获取请求的uri
      $canonicalUri = isset($_SERVER['REQUEST_URI'])?$_SERVER['REQUEST_URI']:'';
      //去除//
      $canonicalUri = substr($canonicalUri, 0, 2)==='//'?substr($canonicalUri, 1):$canonicalUri;
      $canonicalUris = parse_url($canonicalUri);
      $canonicalPath = isset($canonicalUris['path'])?$canonicalUris['path']:'';
      //取得query
      $canonicalQuery = isset($canonicalUris['query'])?$canonicalUris['query']:'';
      //取得path
      $canonicalPath = substr($canonicalPath, 0, 1)==='/'?$canonicalPath:('/'.$canonicalPath);
      $canonicalPath = $this->urlEncodeExceptSlash($canonicalPath);

      // 重新排序编码
      $canonicalQuery = $this->canonicalQuerySort($canonicalQuery);

      // 获取签名头
      $canonicalHeaders = $this->getCanonicalHeaders($signHeaders);

      //生成需要签名的信息体
      $canonicalRequest = "{$this->method}\n{$canonicalPath}\n{$canonicalQuery}\n{$canonicalHeaders}";
      //服务端模拟客户端算出的签名信息
      $sessionSignCheck = hash_hmac('sha256', $canonicalRequest, $signingKey);

      // **** 处理

      //签名通过，接下来检测content_md5
      if($sessionSignCheck!==$clientSign){
        $errorData = array('debugSign'=>array());
        $errorData['debugSign']['canonicalRequest'] = $canonicalRequest;
        $errorData['debugSign']['requestId'] = $requestId;
        $errorData['debugSign']['sessionId'] = $sessionId;
        $errorData['debugSign']['sessionCard'] = $sessionCard;
        $errorData['debugSign']['clientSign'] = $clientSign;
        $errorData['debugSign']['signHeaderKeysStr'] = $signHeaderKeysStr;
        $errorData['debugSign']['serverSessionKey'] = $data['key'];
        $errorData['debugSign']['signingKey'] = $signingKey;
        $errorData['debugSign']['sessionSignCheck'] = $sessionSignCheck;
        throw new AuthErrorException('Signature authentication failure', 'AUTHORIZATION_SIGNATURE_FAILURE', 403, $errorData);
      }
      $this->signInfo['sessionId'] = $sessionId;
      return true;
    }
    private function getCanonicalHeaders($signHeaders = array())
    {
      //把系统头和自定义头合并
      $canonicalHeader = array();
      foreach ($signHeaders as $key => $value) {
        $canonicalHeader[] = strtolower($this->urlEncode(trim($key))).':'.$this->urlEncode(trim($value));
      }
      sort($canonicalHeader);
      //服务器模拟客户端生成的头
      $canonicalHeader = implode("\n", $canonicalHeader) ;
      return $canonicalHeader;
    }
    private function canonicalQuerySort($canonicalQuery = '')
    {
      //拆分get请求的参数
      $canonicalQuery = empty($canonicalQuery) ? array() : explode('&',$canonicalQuery);
      $tempNew = array();
      $temp = '';
      $tempI = '';
      $tempKey = '';
      $tempValue = '';
      foreach ($canonicalQuery as $key => $temp) {
        $temp = $this->urlDecode($temp);
        $tempI = strpos($temp,'=');
        if (strpos($temp,'=')===false) {
          continue;
        }
        $tempKey = substr($temp, 0,$tempI);
        $tempValue = substr($temp, $tempI+1);
        
        $tempNew[] = $this->urlEncode($tempKey).'='.$this->urlEncode($tempValue);
      }
      sort($tempNew);
      $canonicalQuery = implode('&', $tempNew) ;
      unset($temp,$tempI,$tempKey,$tempValue,$tempNew);
      return $canonicalQuery;
    }
    private function getSignHeaders($signHeaderKeys = array())
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
        $authHeaderKey = str_replace('-','_',$signHeaderKeys[$i]?$signHeaderKeys[$i]:'');
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
    private function getSignHeaderKeysByStr($signHeaderKeysStr = '')
    {
      //被签名的头的key，包括自定义和系统
      $signHeaderKeysStr = (is_string($signHeaderKeysStr)||is_numeric($signHeaderKeysStr)) ? (string)$signHeaderKeysStr : '';
      //拆分头键名为数组 方便后期处理
      $signHeaderKeys = explode(';', $signHeaderKeysStr);
      //定义一个空数组来存储对授权头key预处理
      $signHeaderKeysNew = array();
      //遍历授权头的key
      foreach ($signHeaderKeys as $key => $auth_header) {
        //去空格，转小写
        $signHeaderKeysNew[]=strtolower(trim($auth_header));
      }
      //把处理后的头的key覆盖原来的变量，释放内存
      $signHeaderKeys = $signHeaderKeysNew;unset($signHeaderKeysNew);
      //移除数组中重复的值
      $signHeaderKeys = array_unique($signHeaderKeys);
      return $signHeaderKeys;
    }
    private function parseAuth()
    {
      $auths = array();
      // 试图正则匹配
      preg_match($this->regAuth, $this->authorization,$auths);
      // 
      if (count($auths)!==8) {
        throw new AuthErrorException('Authentication Info Length Error','AUTHORIZATION_ERROR_INFO_LENGTH',403);
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
        // 需要签名的头的key
        $signHeaderKeysStr,
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
        throw new AuthErrorException('Request authorization expired!','AUTHORIZATION_REQUEST_EXPIRED',403);
      }elseif (($signTime + $expiredTimeOffset) < time()) {
        //签名期限还没有到
        throw new AuthErrorException('Request authorization has not yet entered into force!','AUTHORIZATION_REQUEST_NOT_ENABLE',403);
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
        // 需要签名的头的key
        $signHeaderKeysStr
      );
    }
  }

 ?>