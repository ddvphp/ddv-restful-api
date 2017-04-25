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
    /**
     * @var PhpMailer
     */
    protected $phpMailer;


    protected function __construct ()
    {
      echo "DdvRestfulApi hello";
    }
    //后门
    public static function getDdvRestfulApi()
    {
      if (self::$ddvRestfulApiObj === null) {
          //实例化一个单例对象
          self::$ddvRestfulApiObj = new self();
      }
      //返回的属性 其实就是本对象
      return self::$ddvRestfulApiObj
    }

  }