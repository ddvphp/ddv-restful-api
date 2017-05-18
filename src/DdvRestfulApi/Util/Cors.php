<?php

  namespace DdvPhp\DdvRestfulApi\Util;

use \DdvPhp\DdvRestfulApi\Exception\Cors as CorsException;
use \DdvPhp\DdvRestfulApi\Exception\OptionsCors as OptionsCorsException;

  /**
   * Class Cors
   *
   * Wrapper around PHPMailer
   *
   * @package DdvPhp\DdvRestfulApi
   */
  class Cors
  {

    public static function init($config)
    {
      if (empty($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_METHOD'])){
        return;
      }
      $control = is_array($config['control']) ? $config['control'] : 7200;
      $origins = is_array($config['origin']) ? $config['origin'] : array();
      $methods = is_array($config['method']) ? $config['method'] : array();
      $allowHeaders = is_array($config['allowHeader']) ? $config['allowHeader'] : array();
      $exposeHeaders = is_array($config['exposeHeader']) ? $config['exposeHeader'] : array();
      //获取请求域名
      $origin = empty($_SERVER['HTTP_ORIGIN'])? '' : $_SERVER['HTTP_ORIGIN'];
      //标记请求方式
      $method = strtoupper(empty($_SERVER['REQUEST_METHOD'])? 'GET' : $_SERVER['REQUEST_METHOD']);
      //标记请求方式
      $originMethod = strtoupper(empty($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_METHOD'])? 'GET' : $_SERVER['HTTP_ACCESS_CONTROL_REQUEST_METHOD']);
      $originsLen = count($origins);
      $originPass = false;
      for ($i=0; $i < $originsLen; $i++) {
        $origint = $origins[$i];
        if ($origin===substr($origint, 0, strlen($origin))) {
          $originPass = true;
          break;
        }else if(preg_match(('/^'.self::getReg($origint).'$/'), $origin)){
          $originPass = true;
          break;
        }
      }
      if (!$originPass) {
        throw new CorsException("No origin is allowed", 'NO_ORIGIN_ALLOWED');
      }
      //通过授权
      @header('Access-Control-Allow-Credentials:true');
      //允许跨域访问的域，可以是一个域的列表，也可以是通配符"*"。这里要注意Origin规则只对域名有效，并不会对子目录有效。即http://foo.example/subdir/ 是无效的。但是不同子域名需要分开设置，这里的规则可以参照同源策略
      @header('Access-Control-Allow-Origin:'.$origin);
      if ($method!=='OPTIONS') {
        return true;
      }
      if (!in_array($originMethod, $methods)) {
        throw new CorsException("No method is allowed", 'NO_METHODS_ALLOWED');
      }

      //请求头
      $originHeadersStr = empty($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS'])? '' : $_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS'];
      //拆分数组
      $originHeaders = explode(',', $originHeadersStr);
      $originHeadersLen = count($originHeaders);
      $allowOriginHeaders = array();
      
      for ($i=0; $i < $originHeadersLen; $i++) {
        $t = $originHeaders[$i];
        $t = trim($t);
        if(!self::checkHeader($t, $allowHeaders)){
          throw new CorsException('No '.$t.' header is allowed', 'NO_HEADER_ALLOWED');
        }
        $allowOriginHeaders[]=$t;
      }
      $allowOriginHeadersStr = implode(', ', $allowOriginHeaders);
      //允许自定义的头部，以逗号隔开，大小写不敏感
      @header('Access-Control-Allow-Headers:'.$allowOriginHeadersStr);
      //允许脚本访问的返回头，请求成功后，脚本可以在XMLHttpRequest中访问这些头的信息(貌似webkit没有实现这个)
      @header('Access-Control-Expose-Headers:set-cookie, request-id, session-sign');
      //允许使用的请求方法，以逗号隔开
      @header('Access-Control-Allow-Methods:'.$originMethod);
      //缓存此次请求的秒数。在这个时间范围内，所有同类型的请求都将不再发送预检请求而是直接使用此次返回的头作为判断依据，非常有用，大幅优化请求次数
      @header('Access-Control-Max-Age:'.$control);
      throw new OptionsCorsException('Allow pass', 'ALLOW_PASS');
    }
    public static function checkHeader($originHeader='', $allowHeaders=array())
    {
      $allowHeaderPasst = false;
      $allowHeadersLen = count($allowHeaders);
      for ($i=0; $i < $allowHeadersLen; $i++) {
        $headert = $allowHeaders[$i];
        if ($originHeader===substr($headert, 0, strlen($originHeader))) {
          $allowHeaderPasst = true;
          break;
        }else if(preg_match(('/^'.self::getReg($headert).'$/'), $originHeader)){
          $allowHeaderPasst = true;
          break;
        }
      }
      return $allowHeaderPasst;
    }
    public static function getReg($url='')
    {
      $reg = preg_replace_callback(
          '([\*\.\?\+\$\^\[\]\(\)\{\}\|\\\/])',
          function ($matches) {
            if ($matches[0]==='*') {
              return '(.*)';
            }else{
              return '\\'.$matches[0];
            }
          },
          $url
      );
      return $reg;
    }
  }
?>