<?php 
  namespace DdvPhp\DdvRestfulApi\Auth;
  use \DdvPhp\DdvRestfulApi\Exception\AuthError as AuthErrorException;
  /**
  * 
  */
  class AuthSignSessionBase
  {
      // 根据RFC 3986，除了：
      //   1.大小写英文字符
      //   2.阿拉伯数字
      //   3.点'.'、波浪线'~'、减号'-'以及下划线'_'
      // 以外都要编码
    protected $PERCENT_ENCODED_STRINGS;
    protected $authorization = null;
    protected $signInfo = null;
    public function __construct($authorization = null, $signInfo = null)
    {
      $this->authorization = $authorization ;
      $this->signInfo = $signInfo ;
      // 检测基本数据
      $this->checkBaseData();
      // url编码初始化
      $this->urlEncodeInit();
    }
    protected function checkBaseData($authorization = null, $signInfo = null)
    {
      if (!$this->authorization) {
        throw new AuthErrorException('Authentication Empty Error', 'AUTHENTICATION_EMPTY_ERROR', '403');
      }
      if (!$this->signInfo) {
        throw new AuthErrorException('Authentication Sign Info Empty Error', 'AUTHENTICATION_SIGN_INFO_EMPTY_ERROR', '403');
      }
    }

      //填充编码数组
    public function urlEncodeInit()
    {
      $this->PERCENT_ENCODED_STRINGS = array();
      for ($i = 0; $i < 256; ++$i) {
        $this->PERCENT_ENCODED_STRINGS[$i] = sprintf("%%%02X", $i);
      }

          //a-z不编码
      foreach (range('a', 'z') as $ch) {
        $this->PERCENT_ENCODED_STRINGS[ord($ch)] = $ch;
      }

          //A-Z不编码
      foreach (range('A', 'Z') as $ch) {
        $this->PERCENT_ENCODED_STRINGS[ord($ch)] = $ch;
      }

          //0-9不编码
      foreach (range('0', '9') as $ch) {
        $this->PERCENT_ENCODED_STRINGS[ord($ch)] = $ch;
      }

          //以下4个字符不编码
      $this->PERCENT_ENCODED_STRINGS[ord('-')] = '-';
      $this->PERCENT_ENCODED_STRINGS[ord('.')] = '.';
      $this->PERCENT_ENCODED_STRINGS[ord('_')] = '_';
      $this->PERCENT_ENCODED_STRINGS[ord('~')] = '~';
    }

      //在uri编码中不能对'/'编码
    public function urlEncodeExceptSlash($path)
    {
      return str_replace("%2F", "/", self::urlEncode($path));
    }

      //使用编码数组编码
    public function urlEncode($value)
    {
      $result = '';
      for ($i = 0; $i < strlen($value); ++$i) {
        $result .= $this->PERCENT_ENCODED_STRINGS[ord($value[$i])];
      }
      return $result;
    }
  }

 ?>