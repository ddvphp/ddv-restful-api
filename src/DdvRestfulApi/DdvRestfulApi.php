<?php

  namespace DdvPhp\DdvRestfulApi;
  use \DdvPhp\DdvRestfulApi\Util\RequestParse as RequestParse;
  use \DdvPhp\DdvRestfulApi\Util\ResponseParse as ResponseParse;
  use \DdvPhp\DdvRestfulApi\Util\RequestHeaders as RequestHeaders;
  use \DdvPhp\DdvRestfulApi\Exception\Handler as ExceptionHandler;
  use \DdvPhp\DdvRestfulApi\Util\Sign as DdvSign;
  use \DdvPhp\DdvRestfulApi\Util\Cors as CorsException;
  use \DdvPhp\DdvRestfulApi\Exception\OptionsCors as OptionsCorsException;



  /**
   * Class DdvRestfulApi
   *
   * Wrapper around PHPMailer
   *
   * @package DdvPhp\DdvRestfulApi
   */
  class DdvRestfulApi
  {
    private static $ddvRestfulApiObj = null;//属性值为对象,默认为null
    //app请求标识
    protected $headersPrefix = '' ;
    //签名信息
    protected $signInfo = null;
    protected $authSignRun = false ;
    protected $config = array(
      'headersPrefix'=>'x-ddv-',
      'cors'=>array()
    );


    protected function __construct ($config = null)
    {
      $headersPrefix = &$this->config['headersPrefix'];
      $this->config($config);
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
      if(function_exists('env')){
        return env('APP_DEBUG', false);
      }else{
        return defined('APP_DEBUG') && ENVIRONMENT==='development';
      }
    }
    /**
     * [setHandler 输出]
     * @author: 桦 <yuchonghua@163.com>
     * @DateTime 2017-04-26T18:56:12+0800
     */
    public static function echoStr($e)
    {
      return ResponseParse::echoStr($e);
    }
    /**
     * [setHandler 设置错误监听]
     * @author: 桦 <yuchonghua@163.com>
     * @DateTime 2017-04-26T18:56:12+0800
     */
    public function useHandler()
    {
      ExceptionHandler::setHandler($this, 'onHandler');
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
    // 授权模块
    public function authSign ()
    {
      if (!$this->authSignRun) {
        $this->authSignRun = true;
        $this->signInfo = DdvSign::sign($this->requestParse());
      }
      return $this->signInfo;
    }
    // 获取实例化对象
    public static function getInstance($config = array(), $class = null)
    {
      return self::getDdvRestfulApi($config, $class);
    }
    // 获取实例化对象
    public static function getDdvRestfulApi($config = array(), $class = null)
    {
      $class = empty($class)? get_called_class() : $class ;
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

    //生成guid
    public function createGuid() {
      $charid = strtolower(md5(uniqid(mt_rand(), true)));
      $hyphen = chr(45);// "-"
      $uuid = substr($charid, 0, 8).$hyphen.substr($charid, 8, 4).$hyphen.substr($charid,12, 4).$hyphen.substr($charid,16, 4).$hyphen.substr($charid,20,12);
      return $uuid;
    }
  }