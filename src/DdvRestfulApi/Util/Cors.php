<?php
namespace DdvPhp\DdvRestfulApi\Util;

/**
 * Class Cors
 *
 * Wrapper around PHPMailer
 *
 * @package DdvPhp\DdvRestfulApi\Util\Cors
 */
class Cors extends \DdvPhp\DdvCors
{
  public static $headerFn = null;
  public static function configInit(&$config)
  {
    // 授权请求
    $config['method'] = is_array($config['method'])?$config['method']:array();
    // 授权请求头
    $config['allowHeader'] = is_array($config['allowHeader'])?$config['allowHeader']:array();
    // 授权响应头读取
    $config['exposeHeader'] = is_array($config['exposeHeader'])?$config['exposeHeader']:array();
    // 缓存时间
    $config['control'] = is_numeric($config['control'])?intval($config['control']):7200;

    $config['method'][] = 'GET';
    $config['method'][] = 'POST';
    $config['method'][] = 'PUT';
    $config['method'][] = 'HEAD';
    $config['method'][] = 'PATCH';
    $config['method'][] = 'DELETE';
    
    $config['allowHeader'][] = 'accept';
    $config['allowHeader'][] = 'origin';
    $config['allowHeader'][] = 'cookie';
    $config['allowHeader'][] = 'authorization';
    $config['allowHeader'][] = 'content-md5';
    $config['allowHeader'][] = 'content-type';
    $config['allowHeader'][] = 'x-requested-with';
    $config['allowHeader'][] = 'x_requested_with';

    $config['exposeHeader'][] = 'set-cookie';
    $config['exposeHeader'][] = 'x-ddv-request-id';
    $config['exposeHeader'][] = 'x-ddv-sign';
  }
  public static function setHeaderFn(\Closure $fn)
  {
    self::$headerFn = $fn;
  }
  // 重新头输出方式
  public static function header($header)
  {
    $headerFn = self::$headerFn;
    if ($headerFn instanceof \Closure) {
      $headerFn($header);
    }else{
      @header($header);
    }
  }
}
