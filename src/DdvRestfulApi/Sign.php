<?php

  namespace DdvPhp\DdvRestfulApi;
  use \DdvPhp\DdvRestfulApi\Exception\AuthError as AuthErrorException;
  use \DdvPhp\DdvRestfulApi\Exception\AuthEcho as AuthEchoException;


  /**
   * Class Sign
   *
   * Wrapper around PHPMailer
   *
   * @package DdvPhp\DdvRestfulApi
   */
  class Sign
  {

    public static function sign($signInfo)
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
      //卸载
      unset($start);
      if (empty($v)) {
        throw new AuthErrorException('Authentication Version Error', 'AUTHENTICATION_VERSION_ERROR', '403');
      }
      if (empty($a2)) {
        throw new AuthErrorException('Authentication Info Error', 'AUTHENTICATION_INFO_ERROR', '403');
      }
      $className = 'AuthSign'.ucfirst(preg_replace_callback(
        '(\-\w)', 
        function ($matches) {
          return strtoupper(substr($matches[0], 1));
        },
        $v
      ));
      $file = dirname(__FILE__) . '/Auth/'.$className.'.php';
      // 判断是否存在该文件
      if (!is_file($file)) {
        throw new AuthErrorException('Authentication Version File Not Find', 'AUTHENTICATION_VERSION_FILE_NOT_FIND', '403');
      }
      // 引入文件
      require_once $file;
      // 加入命名空间
      $className = '\\DdvPhp\\DdvRestfulApi\\Auth\\'.$className;
      if (!class_exists($className)) {
        throw new AuthErrorException('Authentication Version Class Not Find', 'AUTHENTICATION_VERSION_CLASS_NOT_FIND', '403');
      }
      // 实例化该文件
      $authObj = new $className($a2, $signInfo);
      // 回收部分变量
      unset($authorization, $v, $a2, $className, $file, $signInfo);
      // 签名
      $sign = $authObj->runSign();
      throw new AuthEchoException($sign);
      
    }
  }
?>