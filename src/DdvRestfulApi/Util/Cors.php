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
