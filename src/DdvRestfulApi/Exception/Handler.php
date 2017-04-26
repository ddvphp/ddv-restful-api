<?php
 namespace DdvPhp\DdvRestfulApi\Exception;
/**
* 
*/
final class Handler
{
  
  //app请求标识
  private static $isSetErrorHandlerInit = false ;
  private static $isSetExceptionHandlerInit = false ;
  public function __construct()
  {
    throw new NotNewClassError("This Handler class does not support instantiation");
  }
  public static function setHandler(){
      self::setErrorHandlerInit();
      self::setExceptionHandlerInit();
  }
  public static function setErrorHandlerInit(){
    if (self::$isSetErrorHandlerInit!==false) {
      return;
    }
    self::$isSetErrorHandlerInit = true;
    // 设置用户定义的错误处理函数
    if (function_exists('set_error_handler')) {
     // set_error_handler(array('DdvPhp\DdvRestfulApi\Exception\Handler','errorHandler'));
    }
  }
  public static function setExceptionHandlerInit(){
    if (self::$isSetExceptionHandlerInit!==false) {
      return;
    }
    self::$isSetExceptionHandlerInit = true;
    //设置异常处理
    if (function_exists('set_exception_handler')) {
      set_exception_handler(array('DdvPhp\DdvRestfulApi\Exception\Handler','catchException'));
    }
  }
  public static function emitHandler($e){
    $e = is_array($e)?$e:array();
    if($e['code']===0){
      $e['code'] = 500 ;
    }
    $e['message'] = empty($e['message'])?'':$e['message'];
    echo json_encode($e);
  }
  public static function catchException($e){
    //默认错误行数
    $line = 0 ;
    if (method_exists($e,'getLine')) {
      $line =$e->getLine();
    }
    $r = array();
    if (method_exists($e,'getCode')) {
      $r['code'] =$e->getCode();
    }
    if (method_exists($e,'getErrorId')) {
      $r['errorId'] =$e->getErrorId();
    }else{
      $r['errorId'] ='UNKNOWN_ERROR';
    }
    if (method_exists($e,'getMessage')) {
      $r['message'] = $e->getMessage();
    }else{
      $r['message'] ='UNKNOWN_ERROR';
      $r['errorId'] =empty($r['errorId'])?'Unknown Error':$r['errorId'];
    }
    if (method_exists($e,'getResponseData')) {
      $r = array_merge($e->getResponseData(), $r);
    }
    //调试模式
    if (ENVIRONMENT==='development') {
      $r['debug'] = array();
      $r['debug']['type'] = get_class($e);
      $r['debug']['line'] = $line;
      $r['debug']['file'] = $e->getFile();
      $r['debug']['trace'] = $e->getTraceAsString();
      $r['debug']['trace'] = explode("\n", $r['debug']['trace']);
    }
    self::emitHandler($r);
  }
  // 用户定义的错误处理函数
  public static function errorHandler2($errno, $errstr, $errfile, $errline){
    var_dump(333);
  }
  // 用户定义的错误处理函数
  public static function errorHandler($errno, $errstr, $errfile, $errline){

     echo "\n\n";
     var_dump($errno, $errstr, $errfile, $errline);
     throw new \Exception("Error Processing Request", 1);
     
  }
}
?>