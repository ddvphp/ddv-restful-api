<?php 
namespace DdvPhp\DdvRestfulApi\Auth;
use \DdvPhp\DdvRestfulApi\Exception\AuthError as AuthErrorException;
/**
* 
*/
class AuthSignDdvUrlV1 extends AuthAbstract
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
    if (empty($this->authorization)) {
      throw new AuthErrorException('Authentication Info Length Error','AUTHORIZATION_ERROR_INFO_LENGTH',403);
    }
    try {
      $authorization = preg_replace('/\_/', '/', preg_replace('/\-/', '+', $this->authorization));
    } catch (Exception $e) {
      throw new AuthErrorException('Authentication Parse Error','AUTHENTICATION_PARSE_ERROR',403);
    }
    try {
      $authorization = base64_decode($authorization);
    } catch (Exception $e) {
      throw new AuthErrorException('Authentication Base64 Decode Error','AUTHENTICATION_BASE64_DECODE_ERROR',403);
    }
    try {
      $clientSign = substr($authorization, -16);
      if (empty($clientSign)) {
        throw new AuthErrorException('Authentication client sign must input','AUTHENTICATION_CLIENT_SIGN_MUST_INPUT',403);
      }
    } catch (Exception $e) {
      throw new AuthErrorException('Authentication client sign must input','AUTHENTICATION_CLIENT_SIGN_MUST_INPUT',403);
    }
    try {
      $authorization = substr($authorization, 0, -16);
    } catch (Exception $e) {
      throw new AuthErrorException('Authentication wrong format as content','AUTHORIZATION_ERROR_FORMAT_WRONG',403);
    }
    if (empty($authorization)) {
      throw new AuthErrorException('Authentication Info Length Error','AUTHORIZATION_ERROR_INFO_LENGTH',403);
    }
    try {
      $authorization = gzuncompress($authorization);
    } catch (Exception $e) {
      throw new AuthErrorException('Authentication gzuncompress error','AUTHENTICATION_GZUNCOMPRESS_ERROR',403);
    }
    try {
      $authorization = json_decode($authorization, true);
    } catch (Exception $e) {
      throw new AuthErrorException('Authentication json decode error','AUTHENTICATION_JSON_DECODE_ERROR',403);
    }
    $noSignQuery = (!empty($authorization[3]))&&is_array($authorization[3])?$authorization[3]:array();
    $headersKeys = (!empty($authorization[4]))&&is_array($authorization[4])?$authorization[4]:array();

    try {
      @list($sessionId, $signTime, $expiredTimeOffset) = $authorization;
    } catch (Exception $e) {
      throw new AuthErrorException('Authentication json decode error','AUTHENTICATION_JSON_DECODE_ERROR',403);
    }
    if (empty($sessionId)) {
      throw new AuthErrorException('Authentication sessionId Error','AUTHENTICATION_SESSIONID_ERROR',403);
    }

    // 授权数据
    $data = $this->getAuthData($sessionId);

    if (empty($data)||empty($data['card'])||empty($data['key'])) {
      throw new AuthErrorException('Authentication auth data empty','AUTHENTICATION_AUTH_DATA_EMPTY',403);
    }
    $sessionCard = $data['card'];

    // 通过
    $signHeaders = $this->getSignHeaders($headersKeys);
    // 授权字符串
    $authString = "{$this->authVersion}/{$sessionId}/{$sessionCard}/{$signTime}/{$expiredTimeOffset}";
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
    $canonicalPath = self::urlEncodeExceptSlash($canonicalPath);

    // 重新排序编码
    $canonicalQuery = self::canonicalQuerySort($canonicalQuery);
    if ($canonicalQuery) {
      $tPrefix = $this->signBaseHeadersPrefix;
      $authPrefixs = array(
        strtolower($tPrefix.'auth'),
        strtolower($tPrefix.'authorization'),
      );
      $canonicalQueryArray = explode('&', $canonicalQuery);
      $canonicalQueryArrayNew = array();
      foreach ($canonicalQueryArray as $key => $t) {
        $ts = explode('=', $t);
        if (!($ts && $ts[0] && in_array(strtolower($ts[0]), $authPrefixs))) {
          $canonicalQueryArrayNew[] = $t;
        }
      }
      $canonicalQuery = implode('&', $canonicalQueryArrayNew);
    }

    // 获取签名头
    $canonicalHeaders = self::getCanonicalHeaders($signHeaders);
    //生成需要签名的信息体
    $canonicalRequest = "{$this->method}\n{$canonicalPath}\n{$canonicalQuery}\n{$canonicalHeaders}";

    if (empty($noSignQuery)) {
      $canonicalRequest.="\n";
    }else{
      $canonicalRequest.="\n".implode(',', $noSignQuery);
    }
    if (empty($headersKeys)) {
      $canonicalRequest.="\n";
    }else{
      $canonicalRequest.="\n".implode(',', $headersKeys);
    }

    $signCheck = hash_hmac('md5', $canonicalRequest, $signingKey, true);

    if ($clientSign !== $signCheck) {
      $errorData = array('debugSign'=>array());
      $errorData['debugSign']['canonicalRequest'] = $canonicalRequest;
      $errorData['debugSign']['sessionId'] = $sessionId;
      $errorData['debugSign']['sessionCard'] = $sessionCard;
      $errorData['debugSign']['clientSign'] = $clientSign;
      $errorData['debugSign']['signHeaderKeysStr'] = $headersKeys;
      $errorData['debugSign']['serverSessionKey'] = $data['key'];
      $errorData['debugSign']['signingKey'] = $signingKey;
      $errorData['debugSign']['sessionSignCheck'] = $signCheck;
      throw new AuthErrorException('Signature authentication failure', 'AUTHORIZATION_SIGNATURE_FAILURE', 403, $errorData);
    }

    $this->signInfo['sessionId'] = $sessionId;
    return true;
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
  public static function getSignUrl($sessionId, $authData, $path = '/', $query = array(), $noSignQuery = array(), $method = 'GET', $headers = array(), $authClassName = null)
  {
    $expiredTimeOffset = 1800;
    $signTime = time();
    $sessionKey = $authData['key'];
    $sessionCard = $authData['card'];
    if (empty($sessionKey)) {
      throw new AuthErrorException('Auth key empty', 'MUST_AUTH_KEY', 400);
    }
    if (empty($sessionCard)) {
      throw new AuthErrorException('Auth card empty', 'MUST_AUTH_CARD', 400);
    }

    $path = '/'.implode('/', array_filter(explode('/', $path)));
    $pathArray = explode('?', $path);
    $path = $pathArray[0];
    if (!empty($pathArray[1])) {
      parse_str($pathArray[1], $pathQuery);
      if (is_array($pathQuery)) {
        $query = array_merge($pathQuery, $query);
      }
    }
    $path = self::urlEncodeExceptSlash($path);
    $noSignQuery = empty($noSignQuery)?array():$noSignQuery;
    $headersKeys = empty($headers)?array():array_keys($headers);
    
    // 编码
    $query = self::buildQuery($query);
    // 重新排序编码
    $canonicalQuery = self::canonicalQuerySort($query);

    $url = $path;
    if ($query) {
      $url.='?'.$query;
    }
    // 获取签名头
    $canonicalHeaders = self::getCanonicalHeaders($headers);

    // 授权字符串
    $authString = "ddv-url-v1/{$sessionId}/{$sessionCard}/{$signTime}/{$expiredTimeOffset}";
    //生成加密key
    $signingKey = hash_hmac('sha256', $authString, $sessionKey);
    //生成需要签名的信息体
    $canonicalRequest = "{$method}\n{$path}\n{$canonicalQuery}\n{$canonicalHeaders}";
    if (empty($noSignQuery)) {
      $canonicalRequest.="\n";
    }else{
      $canonicalRequest.="\n".implode(',', $noSignQuery);
    }
    if (empty($headersKeys)) {
      $canonicalRequest.="\n";
    }else{
      $canonicalRequest.="\n".implode(',', $headersKeys);
    }
    $signInfo = array(
      $sessionId,
      $signTime,
      $expiredTimeOffset,
      $noSignQuery,
      $headersKeys
    );
    //服务端模拟客户端算出的签名信息
    $signJson = gzcompress(json_encode($signInfo),9).hash_hmac('md5', $canonicalRequest, $signingKey, true);
    $signBase64 = base64_encode($signJson);
    $signBase64 = preg_replace('/\//', '_', preg_replace('/\+/', '-', $signBase64));
    $ddvRestfulApi = \DdvPhp\DdvRestfulApi::getInstance();
    $url .= (strpos($url, '?')===false ? '?' : '&').$ddvRestfulApi->getHeadersPrefix().'auth=ddv-url-v1%2F'.$signBase64;
    return $url;
  }

  private static function getCanonicalHeaders($signHeaders = array())
  {
    //把系统头和自定义头合并
    $canonicalHeader = array();
    foreach ($signHeaders as $key => $value) {
      $canonicalHeader[] = strtolower(self::urlEncode(trim($key))).':'.self::urlEncode(trim($value));
    }
    sort($canonicalHeader);
    //服务器模拟客户端生成的头
    $canonicalHeader = implode("\n", $canonicalHeader) ;
    return $canonicalHeader;
  }
  private static function canonicalQuerySort($canonicalQuery = '')
  {
    //拆分get请求的参数
    $canonicalQuery = empty($canonicalQuery) ? array() : explode('&',$canonicalQuery);
    $tempNew = array();
    $temp = '';
    $tempI = '';
    $tempKey = '';
    $tempValue = '';
    foreach ($canonicalQuery as $key => $temp) {
      $temp = self::urlDecode($temp);
      $tempI = strpos($temp,'=');
      if (strpos($temp,'=')===false) {
        continue;
      }
      $tempKey = substr($temp, 0,$tempI);
      $tempValue = substr($temp, $tempI+1);
      
      $tempNew[] = self::urlEncode($tempKey).'='.self::urlEncode($tempValue);
    }
    sort($tempNew);
    $canonicalQuery = implode('&', $tempNew) ;
    unset($temp,$tempI,$tempKey,$tempValue,$tempNew);
    return $canonicalQuery;
  }
  private static function buildQuery($queryData=array()){
    return http_build_query($queryData);
  }
}
 ?>