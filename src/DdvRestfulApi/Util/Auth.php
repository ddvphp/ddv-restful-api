<?php

namespace DdvPhp\DdvRestfulApi\Util;
use \DdvPhp\DdvRestfulApi\Exception\AuthError as AuthErrorException;


/**
 * Class Auth
 *
 * Wrapper around PHPMailer
 *
 * @package DdvPhp\DdvRestfulApi
 */
class Auth
{

  private static $auth = null;
  public static $PERCENT_ENCODED_STRINGS = array();
  public static function auth(&$signInfo, &$config)
  {
    if (empty($signInfo['header'])) {
      throw new AuthErrorException('get header fail', 'GET_HEADER_FAIL', '403');
    }

    // 获取授权信息
    $authorization = trim($signInfo['header']['authorization']);
    //试图查找/，判断是否是合法的授权信息
    $start = strpos($authorization, '/');
    //没有找到就不合法
    if ($start===false) {
      throw new AuthErrorException('Authentication Format Error', 'AUTHENTICATION_FORMAT_ERROR', '403');
    }
    //提取版本
    $v = substr($authorization,0, $start);
    //提取授权信息
    $a2 = substr($authorization, ($start+1));
    $signInfo['authVersion'] = $v;
    //卸载
    unset($start);
    if (empty($v)) {
      throw new AuthErrorException('Authentication Version Error', 'AUTHENTICATION_VERSION_ERROR', '403');
    }
    if (empty($a2)) {
      throw new AuthErrorException('Authentication Info Error', 'AUTHENTICATION_INFO_ERROR', '403');
    }
    // 中杠转驼峰
    $className = 'AuthSign'.ucfirst(preg_replace_callback(
      '(\-\w)',
      function ($matches) {
        return strtoupper(substr($matches[0], 1));
      },
      $v
    ));
    $config['authClassDirs'] = empty($config['authClassDirs'])?array():$config['authClassDirs'];
    array_unshift($config['authClassDirs'], '\\DdvPhp\\DdvRestfulApi\\Auth\\');
    foreach ($config['authClassDirs'] as $index => $dir) {
      if (class_exists($dir.$className)) {
        // 加入命名空间
        $className = $dir.$className;
      }
    }
    if (!class_exists($className)) {
      throw new AuthErrorException('Authentication Version Class Not Find', 'AUTHENTICATION_VERSION_CLASS_NOT_FIND', '403');
    }
    // 实例化该文件
    $authObj = new $className($a2, $signInfo, $config);

    if ($authObj instanceof \DdvPhp\DdvRestfulApi\Auth\AuthAbstract) {
      self::$auth = $authObj;
      // 回收部分变量
      unset($authorization, $v, $a2, $className, $file, $signInfo);
      // 签名
      $authObj->runSign();
    }else{
      throw new AuthErrorException('Did not find inheritance \DdvPhp\DdvRestfulApi\Auth\AuthAbstract processing class', 'MUST_EXTENDS_AUTH_ABSTRACT', '403');
    }
    
  }
  public static function getAuthData($sessionId)
  {
    $authObj = self::$auth;
    if ($authObj instanceof \DdvPhp\DdvRestfulApi\Auth\AuthAbstract) {
      return $authObj->getAuthData($sessionId);
    }
    throw new AuthErrorException('Auth authentication must be performed first', 'MUST_RUN_AUTH_VERIFICATION', 400);
    
  }
  public static function saveAuthData($sessionId, $data = null)
  {
    $authObj = self::$auth;
    if ($authObj instanceof \DdvPhp\DdvRestfulApi\Auth\AuthAbstract) {
      return $authObj->saveAuthData($sessionId, $data);
    }
    throw new AuthErrorException('Auth authentication must be performed first', 'MUST_RUN_AUTH_VERIFICATION', 400);
    
  }
  public static function getSignUrl($sessionId = null, $path = '/', $query = array(), $noSignQuery = array(), $method = 'GET', $headers = array(), $authClassName = null)
  {
    if (empty($sessionId)) {
      throw new AuthErrorException('session id must input', 'MUST_INPUT_SESSION_ID', 400);
    }
    $authObj = self::$auth;
    $authData = null;
    if ($authObj instanceof \DdvPhp\DdvRestfulApi\Auth\AuthAbstract) {
      $authData = $authObj->getAuthData($sessionId);
    }
    if (empty($authData)) {
      throw new AuthErrorException('auth data not find', 'AUTH_DATA_NOT_FIND', 400);
    }
    if (empty($authClassName)) {
      $authClassName = \DdvPhp\DdvRestfulApi\Auth\AuthSignDdvUrlV1::class;
    }
    if (!class_exists($authClassName)) {
      throw new AuthErrorException('Authentication Version Class Not Find', 'AUTHENTICATION_VERSION_CLASS_NOT_FIND', 400);
    }
    if (!method_exists($authClassName, 'getSignUrl')) {
      throw new AuthErrorException('Authentication Version Class Not support getSignUrl', 'AUTHENTICATION_VERSION_CLASS_NOT_SUPPORT_GET_SIGN_URL', 400);
    }
    return $authClassName::getSignUrl($sessionId, $authData, $path, $query, $noSignQuery, $method, $headers, $authClassName);
  }
  public static function urlEncodeInit()
  {
    // 根据RFC 3986，除了：
    //   1.大小写英文字符
    //   2.阿拉伯数字
    //   3.点'.'、波浪线'~'、减号'-'以及下划线'_'
    // 以外都要编码
    self::$PERCENT_ENCODED_STRINGS = array();
    for ($i = 0; $i < 256; ++$i) {
      self::$PERCENT_ENCODED_STRINGS[$i] = sprintf("%%%02X", $i);
    }

        //a-z不编码
    foreach (range('a', 'z') as $ch) {
      self::$PERCENT_ENCODED_STRINGS[ord($ch)] = $ch;
    }

        //A-Z不编码
    foreach (range('A', 'Z') as $ch) {
      self::$PERCENT_ENCODED_STRINGS[ord($ch)] = $ch;
    }

        //0-9不编码
    foreach (range('0', '9') as $ch) {
      self::$PERCENT_ENCODED_STRINGS[ord($ch)] = $ch;
    }

        //以下4个字符不编码
    self::$PERCENT_ENCODED_STRINGS[ord('-')] = '-';
    self::$PERCENT_ENCODED_STRINGS[ord('.')] = '.';
    self::$PERCENT_ENCODED_STRINGS[ord('_')] = '_';
    self::$PERCENT_ENCODED_STRINGS[ord('~')] = '~';
  }
  //在uri编码中不能对'/'编码
  public static function urlEncodeExceptSlash($path)
  {
    return str_replace("%2F", "/", self::urlEncode($path));
  }

  //使用编码数组编码
  public static function urlEncode($value)
  {
    $result = '';
    for ($i = 0; $i < strlen($value); ++$i) {
      $result .= self::$PERCENT_ENCODED_STRINGS[ord($value[$i])];
    }
    return $result;
  }

  //使用编码数组编码

  //使用编码数组编码
  public static function urlDecode($value)
  {
    return urldecode($value);
  }
}
Auth::urlEncodeInit();
?>