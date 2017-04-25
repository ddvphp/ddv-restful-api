<?php

  namespace DdvPhp\DdvRestfulApi;


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
    //请求头
    protected $header = array();
    //app请求标识
    protected $headersPrefix = 'x-ddv-' ;
    //签名信息
    protected $signInfo = array();


    protected function __construct ($config = null)
    {
      $config = is_array($config)?$config:array();
      if (isset($config['headersPrefix'])) {
        $this->headersPrefix = is_null($config['headersPrefix']) ? $this->headersPrefix : $config['headersPrefix'];
      }
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
    public function requestParse ()
    {
      $this->requestParseByHttp();
    }
    protected function requestParseByHttp ()
    {
      $signInfo = &$this->signInfo;
      $signInfo['type'] = 'http';
      //获取头
      $signInfo['header'] = $this->getHttpHeadersAsSysXAuth();
      $signInfo['header'] = is_array($signInfo['header'])?$signInfo['header']:array();
      if(empty($signInfo['header'])||empty($signInfo['header']['sys'])||empty($signInfo['header']['sys']['content-length'])||$signInfo['header']['sys']['content-length']<1){
        return false;
      }
      //获取原始的类型
      $contentTypeOrigin = $signInfo['header']['sys']['content-type'] ;
      //拆分字符串为数组
      $contentTypeArray = explode( ';', $contentTypeOrigin );
      //函数删除数组中第一个元素，并返回被删除元素的值
      $contentType = strtolower(array_shift( $contentTypeArray ));
      var_dump($contentType);
    }

    //获取头信息
    public function getHttpHeaders($isReload = false){
      $headers = $this->getHttpHeadersAsSysXAuth($isReload = false);
      return array_merge(
        array('authorization' => $headers['authorization']),
        $headers['sys'],
        $headers['x']
      );
    }
    //获取头信息[授权的]
    public function getHttpHeadersAuth($isReload = false){
      return $this->getHttpHeadersAsSysXAuth($isReload = false)['authorization'];
    }
    //获取头信息[自定义]
    public function getHttpHeadersX($isReload = false){
      return $this->getHttpHeadersAsSysXAuth($isReload = false)['x'];
    }
    //获取头信息[系统]
    public function getHttpHeadersSys($isReload = false){
      return $this->getHttpHeadersAsSysXAuth($isReload = false)['sys'];
    }
    //获取签名信息
    public function getHttpHeadersAsSysXAuth($isReload = false){

      if (!(empty($this->header)||$isReload)) {
        return $this->header;
      }
      $header = &$this->header;
      $header['sys'] = array();
      $header['x'] = array();
      $header['authorization'] = '';

      $headersPrefix = str_replace('-','_',strtolower($this->headersPrefix));
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
          $header['sys']['content-type'] =  $_SERVER['REDIRECT_RESTFUL_MULTIPART_TYPE'];
        }elseif (strpos( $header['sys']['content-type'],'multipart/restful-form-data')!==false&&isset($_SERVER['REDIRECT_HTTP_CONTENT_TYPE'])) {
          $header['sys']['content-type'] =  $_SERVER['REDIRECT_HTTP_CONTENT_TYPE'];
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

    //生成guid
    public function createGuid() {
      $charid = strtolower(md5(uniqid(mt_rand(), true)));
      $hyphen = chr(45);// "-"
      $uuid = substr($charid, 0, 8).$hyphen.substr($charid, 8, 4).$hyphen.substr($charid,12, 4).$hyphen.substr($charid,16, 4).$hyphen.substr($charid,20,12);
      return $uuid;
    }
  }