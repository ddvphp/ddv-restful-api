<?php

  namespace DdvPhp\DdvRestfulApi;

use \DdvPhp\DdvRestfulApi\Exception\Cors as CorsException;

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
      /*
      $allowHeadersLen = count($allowHeaders);
      $allowHeaderPass = false;
      for ($i=0; $i < $allowHeadersLen; $i++) {
        $methodt = $allowHeaders[$i];
        if ($originMethod===substr($methodt, 0, strlen($originMethod))) {
          $allowHeaderPass = true;
          var_dump(222);
          break;
        }else if(preg_match(('/^'.self::getReg($methodt).'$/'), $originMethod)){
          $allowHeaderPass = true;
          var_dump(2224);
          break;
        }
      }
      if (!$allowHeaderPass) {
        throw new CorsException("No method is allowed", 'NO_METHODS_ALLOWED');
      }*/
      var_dump('initCors', $allowHeaders, $originMethod);
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