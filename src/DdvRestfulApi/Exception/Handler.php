<?php
 namespace DdvPhp\DdvRestfulApi\Exception;
 use \DdvPhp\DdvRestfulApi\Util\ResponseParse as ResponseParse;
 use \DdvPhp\DdvRestfulApi\DdvRestfulApi as DdvRestfulApiClass;
/**
* 
*/
final class Handler
{
  private static $onHandlerObj = null;
  private static $onHandlerMethod = null;
  private static $handlerDir =  __DIR__.'/../handler/';
  //app请求标识
  private static $isSetErrorHandlerInit = false ;
  private static $isSetExceptionHandlerInit = false ;
  public function __construct()
  {
    throw new NotNewClassError("This Handler class does not support instantiation");
  }
  public static function setHandler($obj, $method){
    self::$onHandlerObj = $obj;
    self::$onHandlerMethod = $method;
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
    require_once self::$handlerDir.'error.handler.php';
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
    require_once self::$handlerDir.'exception.handler.php';
  }
  public static function emitHandler($e){
    $e = is_array($e)?$e:array();
    if (intval($e['statusCode'])<100) {
      $e['statusCode'] = 500;
    }
    $e['message'] = empty($e['message'])?'':$e['message'];
    $onHandlerMethod = self::$onHandlerMethod;
    $onHandlerObj = self::$onHandlerObj;
    if ((!empty($onHandlerMethod))&&method_exists($onHandlerObj, $onHandlerMethod)) {
      $onHandlerObj->$onHandlerMethod($e);
    }else{
      if (!$e['isIgnoreError']) {
        ResponseParse::echoStr($e);
      }
    }
  }
  public static function exceptionHandler($e){
    //默认错误行数
    $errline = 0 ;
    if (method_exists($e,'getLine')) {
      $errline =$e->getLine();
    }
    $r = array();
    if (method_exists($e,'getCode')) {
      $r['statusCode'] = $e->getCode();
    }else{
      $r['statusCode'] = 500;
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
      $r['responseData'] =$e->getResponseData();
    }else{
      $r['responseData'] = array();
    }
    if (method_exists($e,'getResponseData')) {
      $r = array_merge($e->getResponseData(), $r);
    }
    //调试模式
    if (DdvRestfulApiClass::getDdvRestfulApi()->isDevelopment()) {
      $r['debug'] = array();
      $r['debug']['type'] = get_class($e);
      $r['debug']['line'] = $errline;
      $r['debug']['file'] = $e->getFile();
      $r['debug']['trace'] = $e->getTraceAsString();
      $r['debug']['trace'] = explode("\n", $r['debug']['trace']);
      $r['debug']['isError'] = false;
      $r['debug']['isIgnoreError'] = false;
    }
    self::emitHandler($r);
  }
  // 用户定义的错误处理函数
  public static function errorHandler($errorCode, $message, $errfile, $errline, $errcontext){
    $isError = (((E_ERROR | E_PARSE | E_COMPILE_ERROR | E_CORE_ERROR | E_USER_ERROR) & $errorCode) === $errorCode);
    $r = array();
    $r['errorCode'] =$errorCode;
    $r['statusCode'] =500;
    $r['errorId'] ='UNKNOWN_ERROR';
    $r['message'] = $message;
    $r['isIgnoreError'] = (($errorCode & error_reporting()) !== $errorCode);
    $r['responseData'] = array();
    //调试模式
    if (DdvRestfulApiClass::getDdvRestfulApi()->isDevelopment()) {
      $r['debug'] = array();
      $r['debug']['type'] = 'Error';
      $r['debug']['line'] = $errline;
      $r['debug']['file'] = $errfile;
      $r['debug']['trace'] = '';
      $r['debug']['isError'] = $isError;
      $r['debug']['isIgnoreError'] = $r['isIgnoreError'];
      try{
        throw new \Exception($message, $errorCode);
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