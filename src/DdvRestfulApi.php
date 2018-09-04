<?php

namespace DdvPhp;

use DdvPhp\DdvRestfulApi\Interfaces\RequestSignInfo as RequestSignInfoInterfaces;
use DdvPhp\DdvRestfulApi\Lib\RequestSignInfo;
use DdvPhp\DdvRestfulApi\Lib\RestfulApi;
use DdvPhp\DdvRestfulApi\Lib\RestfulApiEcho;


/**
 * Class DdvRestfulApi
 *
 * Wrapper around PHPMailer
 *
 * @package DdvPhp\DdvRestfulApi
 */
class DdvRestfulApi
{
    public static $className = null;
    // 属性值为对象,默认为null
    private static $ddvRestfulApiObj = null;

    // 获取实例化对象
    public static function getInstance($config = array(), $class = null)
    {
        return self::getDdvRestfulApi($config, $class);
    }

    // 获取实例化对象
    public static function getDdvRestfulApi($config = array(), $class = null)
    {
        // 第二个参数提前到第一个参数
        if (is_null($class) && is_string($config)) {
            $class = $config;
            $config = array();
        }
        // 判断是否实例化过
        if (empty(DdvRestfulApi::$className)) {
            // 直接使用第一次实例化的类名
            DdvRestfulApi::$className = empty($class) ? RestfulApiEcho::class : $class;
        }
        $class = DdvRestfulApi::$className;
        if (self::$ddvRestfulApiObj === null) {
            //实例化一个单例对象
            self::$ddvRestfulApiObj = empty($class) ? (new self($config)) : (new $class($config));
        }
        //返回的属性 其实就是本对象
        return self::$ddvRestfulApiObj;
    }
}