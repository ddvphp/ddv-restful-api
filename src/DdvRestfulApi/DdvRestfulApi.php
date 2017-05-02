<?php

  namespace DdvPhp\DdvRestfulApi;
  use \DdvPhp\DdvRestfulApi\Util\RequestParse as RequestParse;
  use \DdvPhp\DdvRestfulApi\Util\ResponseParse as ResponseParse;
  use \DdvPhp\DdvRestfulApi\Util\RequestHeaders as RequestHeaders;
  use \DdvPhp\DdvRestfulApi\Exception\Handler as ExceptionHandler;
  use \DdvPhp\DdvRestfulApi\Sign as DdvSign;
  use \DdvPhp\DdvRestfulApi\Cors as DdvCors;



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
    protected $headersPrefix = 'x-ddv-' ;
    //签名信息
    protected $signInfo = null;


    protected function __construct ($config = null)
    {
      $config = is_array($config)?$config:array();
      if (isset($config['headersPrefix'])) {
        $this->setHeadersPrefix($config['headersPrefix']);
      }
    }
    /**
     * [onHandler 监听错误]
     * @author: 桦 <yuchonghua@163.com>
     * @DateTime 2017-04-26T18:55:58+0800
     * @return   [type]                   [description]
     */
    public function onHandler($e)
    {
      if (isset($e['isIgnoreError'])&&$e['isIgnoreError']===true) {
        return;
      }
      if (!empty($e['responseData'])) {
        array_merge($e, $e['responseData']);
      }
      if(isset($e['responseData'])) unset($e['responseData']);
      if(!$this->isDevelopment()){
        if(isset($e['debug'])) unset($e['debug']);
        if(isset($e['isIgnoreError'])) unset($e['isIgnoreError']);
      }
      ResponseParse::echoStr($e);
    }
    public function isDevelopment(){
      return defined('ENVIRONMENT') && ENVIRONMENT==='development';
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
      return DdvCors::init($config);
    }
    // 授权模块
    public function authSign ()
    {
      $this->signInfo = DdvSign::sign($this->requestParse());
      return $this->signInfo;
    }
    // 获取实例化对象
    public static function getInstance()
    {
      return self::getDdvRestfulApi();
    }
    // 获取实例化对象
    public static function getDdvRestfulApi($class = null)
    {
      $class = empty($class)? get_called_class() : $class ;
      if (self::$ddvRestfulApiObj === null) {
        //实例化一个单例对象
        self::$ddvRestfulApiObj = empty($class)?(new self()):(new $class());
      }
      //返回的属性 其实就是本对象
      return self::$ddvRestfulApiObj;
    }
    //获取头信息
    public function getHeadersPrefix(){
      return RequestHeaders::getHeadersPrefix();
    }
    //设置头信息
    public function setHeadersPrefix($headersPrefix = null){
      return RequestHeaders::setHeadersPrefix($headersPrefix);
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