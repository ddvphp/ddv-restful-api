<?php

  namespace DdvPhp;
  use \DdvPhp\DdvRestfulApi\Util\RequestParse as RequestParse;
  use \DdvPhp\DdvRestfulApi\Util\ResponseParse as ResponseParse;
  use \DdvPhp\DdvRestfulApi\Util\RequestHeaders as RequestHeaders;
  use \DdvPhp\DdvRestfulApi\Util\Auth as DdvAuth;
  use \DdvPhp\DdvRestfulApi\Util\Cors as CorsException;
  use \DdvPhp\DdvRestfulApi\Exception\OptionsCors as OptionsCorsException;
  use \DdvPhp\DdvRestfulApi\Exception\AuthError as AuthErrorException;


  /**
   * Class DdvRestfulApi
   *
   * Wrapper around PHPMailer
   *
   * @package DdvPhp\DdvRestfulApi
   */
  class DdvRestfulApi
  {
    // 属性值为对象,默认为null
    private static $ddvRestfulApiObj = null;
    // 返回数组
    public $responseData = array();
    // app请求标识
    protected $headersPrefix = '' ;
    // 签名信息
    protected $signInfo = null;
    protected $authRun = false ;
    protected $config = array(
      'authDataDriver'=>'file',
      'headersPrefix'=>'x-ddv-',
      'authDataDriverConfig'=>array(),
      'cors'=>array()
    );


    protected function __construct ($config = null)
    {
      $headersPrefix = &$this->config['headersPrefix'];
      $this->config($config);
      $this->responseDataInit();
    }
    public function responseDataInit($config = null)
    {
      $this->responseData = array(
        //错误识别码
        'errorId'=>'OK',
        //消息
        'message'=>'',
        //代码
        'code'=>0,
        //数据
        'data'=>null,
        //列表
        'lists'=>[],
        //分页
        'page'=>null
      );
    }
    public function config($config = null)
    {
      if (!is_array($config)) {
        return $this->config;
      }
      foreach ($config as $key => $value) {
        $this->config[$key] = $value;
      }
      return $this;
    }
    /**
     * [onHandler 监听错误]
     * @author: 桦 <yuchonghua@163.com>
     * @DateTime 2017-04-26T18:55:58+0800
     * @return   [type]                   [description]
     */
    public function onHandler($r, $e)
    {
      if ($e instanceof OptionsCorsException) {
        die();
      }
      // if ($e instanceof AuthEchoException) {
      //   die();
      // }
      if (isset($r['isIgnoreError'])&&$r['isIgnoreError']===true) {
        return;
      }
      if (!empty($r['responseData'])) {
        array_merge($r, $r['responseData']);
      }
      if(isset($r['responseData'])) unset($r['responseData']);
      if(!$this->isDevelopment()){
        if(isset($r['debug'])) unset($r['debug']);
        if(isset($r['isIgnoreError'])) unset($r['isIgnoreError']);
      }
      ResponseParse::echoStr($r);
    }
    public function isDevelopment(){
      $isDebug = defined('ENVIRONMENT') && ENVIRONMENT === 'development';
      $isDebug = $isDebug || function_exists('env') && env('APP_DEBUG');
      return $isDebug;
    }
    /**
     * [echoData 输出]
     * @author: 桦 <yuchonghua@163.com>
     * @DateTime 2017-04-26T18:56:12+0800
     */
    public function echoData($data)
    {
      $data = array_merge($this->responseData, $data);
      return $this->echoStr($data);
    }
    /**
     * [echo404 输出]
     * @author: 桦 <yuchonghua@163.com>
     * @DateTime 2017-04-26T18:56:12+0800
     */
    public function echo404($message = 'Api interface not found', $errorId = '404 Not Found', $statusCode=404)
    {
      $responseData = array(
        'statusCode'=>$statusCode,
        'code'=>0,
        'errorId'=>$errorId,
        'message'=>$message
      );
      return $this->echoData($responseData);
    }
    /**
     * [setHandler 输出]
     * @author: 桦 <yuchonghua@163.com>
     * @DateTime 2017-04-26T18:56:12+0800
     */
    public function echoStr()
    {
      return call_user_func_array(array(ResponseParse::class, 'echoStr'), func_get_args());
    }
    /**
     * [setHandler 设置错误监听]
     * @author: 桦 <yuchonghua@163.com>
     * @DateTime 2017-04-26T18:56:12+0800
     */
    public function useHandler()
    {
      \DdvPhp\DdvException\Handler::setHandler(function (array $r, $e) {
        $this->onHandler($r, $e);
      }, function () {
        return $this->isDevelopment();
      });
      return $this;
    }
    // 请求解析
    public function requestParse ()
    {
      if (empty($this->signInfo)) {
        $this->signInfo = RequestParse::requestParse();
      }
      return $this->signInfo;
    }
    // 授权模块
    public function initCors ($config=array())
    {
      if (is_array($config)) {
        foreach ($config as $key => $value) {
          $this->config['cors'][$key] = $value;
        }
      }
      return CorsException::init($this->config['cors']);
    }
    // getSessionId
    public function getSessionId ()
    {
      return $this->signInfo['sessionId'];
    }
    // 授权模块[兼容旧版]
    public function authSign ()
    {
      return $this->auth();
    }
    // 授权模块
    public function auth ()
    {
      if (!$this->authRun) {
        $this->authRun = true;
        $this->requestParse();
        DdvAuth::auth($this->signInfo, $this->config);
      }
      return $this->signInfo;
    }
    // 获取已经索取的数据信息
    public function getAuthData ()
    {
      if (!$this->authRun) {
        throw new AuthErrorException('Auth authentication must be performed first', 'MUST_RUN_AUTH_VERIFICATION');
      }
      return DdvAuth::getAuthData($this->getSessionId());
    }
    // 获取已经索取的数据信息
    public function saveAuthData ($save)
    {
      if (!$this->authRun) {
        throw new AuthErrorException('Auth authentication must be performed first', 'MUST_RUN_AUTH_VERIFICATION');
      }
      return DdvAuth::saveAuthData($this->getSessionId(), $save);
    }
    // 获取实例化对象
    public static function getInstance($config = array(), $class = null)
    {
      return self::getDdvRestfulApi($config, $class);
    }
    // 获取实例化对象
    public static function getDdvRestfulApi($config = array(), $class = null)
    {
      // 第二个参数提前到第一个参数
      if (is_null($class) && is_string($config)) {
        $class = $config;
        $config = array();
      }
      // 判断是否实例化过
      if (empty(newClassNameSaveByStaticClass::$className)) {
        // 直接使用第一次实例化的类名
        newClassNameSaveByStaticClass::$className = empty($class)? get_called_class() : $class ;
      }
      $class = newClassNameSaveByStaticClass::$className ;
      if (self::$ddvRestfulApiObj === null) {
        //实例化一个单例对象
        self::$ddvRestfulApiObj = empty($class)?(new self($config)):(new $class($config));
      }
      //返回的属性 其实就是本对象
      return self::$ddvRestfulApiObj;
    }
    //获取头信息
    public function getHeadersPrefix(){
      return RequestHeaders::getHeadersPrefix();
    }
    //获取头信息
    public function getHttpHeaders($isReload = false){
      return RequestHeaders::getHttpHeaders($isReload);
    }
    //获取头信息[授权的]
    public function getHttpHeadersAuth($isReload = false){
      return RequestHeaders::getHttpHeadersAuth($isReload);
    }
    //获取头信息[自定义]
    public function getHttpHeadersX($isReload = false){
      return RequestHeaders::getHttpHeadersX($isReload);
    }
    //获取头信息[系统]
    public function getHttpHeadersSys($isReload = false){
      return RequestHeaders::getHttpHeadersSys($isReload);
    }
    //获取签名头
    public function getHttpHeadersAsSysXAuth($isReload = false){
      return RequestHeaders::getHttpHeadersAsSysXAuth($isReload);
    }

  }
  /**
  *
  */
  class newClassNameSaveByStaticClass
  {
    public static $className = null;
  }
