<?php 
namespace DdvPhp\DdvRestfulApi\Util;
use \DdvPhp\DdvRestfulApi\Util\RequestHeaders as RequestHeaders;
use \DdvPhp\DdvRestfulApi\Exception\NotNewClassError as NotNewClassError;
use \DdvPhp\DdvRestfulApi\Exception\RequestParseError as RequestParseError;
use \DdvPhp\DdvRestfulApi\Exception\Handler as ExceptionHandler;
use \DdvPhp\DdvRestfulApi\DdvRestfulApi as DdvRestfulApiClass;
/**
* 
*/
final class ResponseParse
{
  public function __construct()
  {
    throw new NotNewClassError("This ResponseParse class does not support instantiation");
  }
  //获取签名信息
  public static function toJsonString($data, $isNotUnescapedUnicode = true){
    if ($isNotUnescapedUnicode!==true) {
      $r = json_encode($data);
    }else{
      if (version_compare(PHP_VERSION,'5.4.0','<'))
      {
        $r = json_encode($data);
        $r = preg_replace_callback(
          "#\\\u([0-9a-f]{4})#i",
          function($matchs)
          {
               return iconv('UCS-2BE', 'UTF-8', pack('H4', $matchs[1]));
          },
          $r
        );
      }
      else
      {
        $r = json_encode($data, JSON_UNESCAPED_UNICODE);
      }
    }
    return $r;
  }
  //获取签名信息
  public static function echoStr($data, $isEcho = true, $isAutoHeader = true, $isAutoSessionClose = true, $isAutoObClean = null, $isNotUnescapedUnicode = true){
    // 关闭会话
    try{
      if ($isAutoSessionClose===true&&function_exists('session_write_close')) {
        @session_write_close();
      }
    }catch(Exception $e){}
    $isAutoObClean = !DdvRestfulApiClass::getDdvRestfulApi()->isDevelopment();

    $statusCode = empty($data['statusCode'])? ( isset($data['errorId'])&&$data['errorId']!=='OK'?500:200 ) : $data['statusCode'] ;
    $statusText = empty($data['errorId']) ? '' : $data['errorId'];
    $statusText = empty($statusText) ? (empty($data['statusText']) ? '' : $data['statusText']) : $statusText;
    $statusText = empty($statusText) ? (($statusCode >= 200 && $statusCode < 300) ? 'OK' : 'UNKNOWN_ERROR') : $statusText;
    if(function_exists('set_status_header')){
      set_status_header($statusCode,$statusText);
    }else{
      try{
        //nginx模式
        if (strpos(PHP_SAPI, 'cgi') === 0){
          header('Status: '.$statusCode.' '.$statusText, TRUE);
        }else{
          $serverProtocol = isset($_SERVER['SERVER_PROTOCOL']) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.1';
          header($serverProtocol.' '.$statusCode.' '.$statusText, TRUE, $statusCode);
          unset($serverProtocol);
        }
      }catch(Exception $e){}
    }
    if ($isAutoHeader===true) {
      @header('Content-Type:application/json;charset=utf-8',true);
    }
    if ($isAutoObClean===true) {
      try{
        ob_clean();
      }catch(Exception $e){}
    }
    if ($isEcho===true) {
      echo self::toJsonString($data);
      die();
    }else{
      return self::toJsonString($data);
    }
  }
}
?>