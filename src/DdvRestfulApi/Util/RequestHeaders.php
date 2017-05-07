<?php 
namespace DdvPhp\DdvRestfulApi\Util;
use \DdvPhp\DdvRestfulApi\Exception\NotNewClassError as NotNewClassError;
/**
* 
*/
final class RequestHeaders
{
  
  public function __construct()
  {
    throw new NotNewClassError("This RequestHeaders class does not support instantiation");
  }
  //请求头
  private static $header = array();
  //app请求标识
  private static $headersPrefix = 'x-ddv-' ;
  //获取头信息前缀
  public static function getHeadersPrefix(){
    return self::$headersPrefix;
  }
  //设置头信息前缀
  public static function setHeadersPrefix($headersPrefix = null){
      self::$headersPrefix = is_null($headersPrefix) ? self::$headersPrefix : $headersPrefix;
  }
  //获取头信息
  public static function getHttpHeaders($isReload = false){
    $headers = self::getHttpHeadersAsSysXAuth($isReload = false);
    return array_merge(
      array('authorization' => $headers['authorization']),
      $headers['sys'],
      $headers['x']
    );
  }
  //获取头信息[授权的]
  public static function getHttpHeadersAuth($isReload = false){
    return self::getHttpHeadersAsSysXAuth($isReload = false)['authorization'];
  }
  //获取头信息[自定义]
  public static function getHttpHeadersX($isReload = false){
    return self::getHttpHeadersAsSysXAuth($isReload = false)['x'];
  }
  //获取头信息[系统]
  public static function getHttpHeadersSys($isReload = false){
    return self::getHttpHeadersAsSysXAuth($isReload = false)['sys'];
  }
  //获取签名信息
  public static function getHttpHeadersAsSysXAuth($isReload = false){

    if (!(empty(self::$header)||$isReload)) {
      return self::$header;
    }
    $headersPrefix = str_replace('-','_',strtolower(self::$headersPrefix));
    $header = &self::$header;
    $header['headersPrefix'] = self::$headersPrefix;
    $header['sys'] = array();
    $header['x'] = array();
    $header['authorization'] = '';

    //所有headers参数传输的前缀
    $headersPrefixLen = strlen($headersPrefix);

    $httpPrefixlen = strlen('http_');
    $header['authorization'] = isset($_SERVER['HTTP_AUTHORIZATION'])?$_SERVER['HTTP_AUTHORIZATION']:'';
    $header['sys']['content-md5'] = isset($_SERVER['HTTP_CONTENT_MD5'])?$_SERVER['HTTP_CONTENT_MD5']:'';
    $header['sys']['content-type'] = isset($_SERVER['HTTP_CONTENT_TYPE'])?$_SERVER['HTTP_CONTENT_TYPE']:'';
    $header['sys']['content-length'] = intval(isset($_SERVER['HTTP_CONTENT_LENGTH'])?$_SERVER['HTTP_CONTENT_LENGTH']:0);
    $header['sys']['host'] = isset($_SERVER['HTTP_HOST'])?$_SERVER['HTTP_HOST']:'';
    foreach ($_SERVER as $key => $value) {
      $key = substr(strtolower($key),$httpPrefixlen);
      if (substr($key,0,$headersPrefixLen)==$headersPrefix) {
        $header['x'][$key] = $value ;
      }
    }
    unset($httpPrefixlen);
    if (function_exists('apache_request_headers')) {
      foreach (apache_request_headers() as $key => $value) {
        $key = strtolower($key);
        if ($key === 'authorization') {
          $header['authorization'] = empty($value) ? $header['authorization'] : $value;
        } elseif ($key === 'content-length') {
          $header['sys']['content-length'] = empty($value) ? $header['sys']['content-length'] : $value;
        }
      }

    }
    if (empty($header['sys']['content-type'])) {
      $header['sys']['content-type'] = isset($_SERVER['CONTENT_TYPE'])?$_SERVER['CONTENT_TYPE']:'';
    }

    if (isset($header['sys']['content-type'])) {
      if (strpos( $header['sys']['content-type'],'multipart/restful-form-data')!==false&&isset($_SERVER['REDIRECT_RESTFUL_MULTIPART_TYPE'])) {
        $header['sys']['content-type'] = $_SERVER['REDIRECT_RESTFUL_MULTIPART_TYPE'];
      }elseif (strpos( $header['sys']['content-type'],'multipart/restful-form-data')!==false&&isset($_SERVER['REDIRECT_HTTP_CONTENT_TYPE'])) {
        $header['sys']['content-type'] = $_SERVER['REDIRECT_HTTP_CONTENT_TYPE'];
      }
    }
    //试图去除端口
    try{
      $parseUrlTemp = parse_url($header['sys']['host']);
      $header['sys']['host'] = isset($parseUrlTemp['host'])?$parseUrlTemp['host']:$header['sys']['host'];
      unset($parseUrlTemp);
    }catch(Exception $e){}
    if(!empty($_GET[$headersPrefix.'authorization'])){
      $header['authorization'] = $_GET[$headersPrefix.'authorization'] ;
      unset($_GET[$headersPrefix.'authorization']);
    }
    //返回
    return $header ;
  }
}
?>