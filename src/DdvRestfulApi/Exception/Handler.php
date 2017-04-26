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
  private static $onHandlers = array();
  public function __construct()
  {
    throw new NotNewClassError("This Handler class does not support instantiation");
  }
  public static function onHandler(&$handler = null){
    self::setHandler();
    if(get_class($handler)==='Closure'){
      self::$onHandlers[] = &$handler;
    }
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
      set_error_handler(array('DdvPhp\DdvRestfulApi\Exception\Handler','errorHandler'));
    }
    require_once __DIR__.'/error.handler.php';
  }
  public static function setExceptionHandlerInit(){
    if (self::$isSetExceptionHandlerInit!==false) {
      return;
    }
    self::$isSetExceptionHandlerInit = true;
    //设置异常处理
    if (function_exists('set_exception_handler')) {
      set_exception_handler(array('DdvPhp\DdvRestfulApi\Exception\Handler','exceptionHandler'));
    }
    require_once __DIR__.'/exception.handler.php';
  }
  public static function emitHandler($e){
    $e = is_array($e)?$e:array();
    if($e['code']===0){
      $e['code'] = 500 ;
    }
    $e['message'] = empty($e['message'])?'':$e['message'];
    $isIgnoreError = false;
    if (isset($e['isIgnoreError'])) {
      $isIgnoreError = (bool)$e['isIgnoreError'];
      unset($e['isIgnoreError']);
    }
    $onHandlersLen = count(self::$onHandlers);
    $emitNum = 0 ;
    for ($i=0; $i < $onHandlersLen; $i++) { 
      $fn = self::$onHandlers[$i];
      if(get_class($fn)==='Closure'){
        $emitNum++ ;
        $fn($e, $isIgnoreError);
      }
    }
    if ($emitNum<1) {
      var_dump(222);
    }
  }
  public static function isDevelopment(){
    return ENVIRONMENT==='development';
  }
  public static function exceptionHandler($e){
    //默认错误行数
    $errline = 0 ;
    if (method_exists($e,'getLine')) {
      $errline =$e->getLine();
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
    if (self::isDevelopment()) {
      $r['debug'] = array();
      $r['debug']['type'] = get_class($e);
      $r['debug']['line'] = $errline;
      $r['debug']['file'] = $e->getFile();
      $r['debug']['trace'] = $e->getTraceAsString();
      $r['debug']['trace'] = explode("\n", $r['debug']['trace']);
      $r['debug']['isError'] = false;
    }
    self::emitHandler($r);
  }
  // 用户定义的错误处理函数
  public static function errorHandler($code, $message, $errfile, $errline, $errcontext){
    $isError = (((E_ERROR | E_PARSE | E_COMPILE_ERROR | E_CORE_ERROR | E_USER_ERROR) & $code) === $code);
    $r = array();
    $r['code'] =$code;
    $r['errorId'] ='UNKNOWN_ERROR';
    $r['message'] = $message;
    $r['isIgnoreError'] = (($code & error_reporting()) !== $code);
    //调试模式
    if (self::isDevelopment()) {
      $r['debug'] = array();
      $r['debug']['type'] = 'Error';
      $r['debug']['line'] = $errline;
      $r['debug']['file'] = $errfile;
      $r['debug']['trace'] = '';
      $r['debug']['isError'] = $isError;
      $r['debug']['isIgnoreError'] = $r['isIgnoreError'];
      try{
        throw new \Exception($message, $code);
      }catch(\Exception $e){
        $r['debug']['trace'] = $e->getTraceAsString();
        $r['debug']['trace'] = explode("\n", $r['debug']['trace']);
        if (count($r['debug']['trace'])>0) {
          $r['debug']['trace'] = array_splice($r['debug']['trace'],2);
        }
      }
    }
    self::emitHandler($r);
  }
}
?>