<?php

  namespace DdvPhp\DdvRestfulApi;
  use DdvPhp\DdvRestfulApi\Util\RequestParse as RequestParse;
  use DdvPhp\DdvRestfulApi\Util\RequestHeaders as RequestHeaders;
  use DdvPhp\DdvRestfulApi\Exception\Handler as ExceptionHandler;



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
    protected $signInfo = array();


    protected function __construct ($config = null)
    {
      $config = is_array($config)?$config:array();
      if (isset($config['headersPrefix'])) {
        $this->setHeadersPrefix($config['headersPrefix']);
      }
    }
    //后门
    public static function onHandler()
    {
      return ExceptionHandler::onHandler();
    }
    //后门
    public static function setHandler()
    {
      return ExceptionHandler::setHandler();
    }
    //后门
    public static function getDdvRestfulApi()
    {
      if (self::$ddvRestfulApiObj === null) {
          //实例化一个单例对象
          self::$ddvRestfulApiObj = new self();
      }
      //返回的属性 其实就是本对象
      return self::$ddvRestfulApiObj;
    }
    // 请求解析
    public function requestParse ()
    {
      $this->signInfo = RequestParse::requestParse();
      return $this->signInfo;
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