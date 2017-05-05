<?php 
  namespace DdvPhp\DdvRestfulApi\Auth;
  use \DdvPhp\DdvRestfulApi\Exception\AuthError as AuthErrorException;

  // 根据RFC 3986，除了：
  //   1.大小写英文字符
  //   2.阿拉伯数字
  //   3.点'.'、波浪线'~'、减号'-'以及下划线'_'
  // 以外都要编码
  $PERCENT_ENCODED_STRINGS = array();
  for ($i = 0; $i < 256; ++$i) {
    $PERCENT_ENCODED_STRINGS[$i] = sprintf("%%%02X", $i);
  }

      //a-z不编码
  foreach (range('a', 'z') as $ch) {
    $PERCENT_ENCODED_STRINGS[ord($ch)] = $ch;
  }

      //A-Z不编码
  foreach (range('A', 'Z') as $ch) {
    $PERCENT_ENCODED_STRINGS[ord($ch)] = $ch;
  }

      //0-9不编码
  foreach (range('0', '9') as $ch) {
    $PERCENT_ENCODED_STRINGS[ord($ch)] = $ch;
  }

      //以下4个字符不编码
  $PERCENT_ENCODED_STRINGS[ord('-')] = '-';
  $PERCENT_ENCODED_STRINGS[ord('.')] = '.';
  $PERCENT_ENCODED_STRINGS[ord('_')] = '_';
  $PERCENT_ENCODED_STRINGS[ord('~')] = '~';
  /**
  * 
  */
  class AuthSignSessionBase
  {
    protected $authorization = null;
    protected $signInfo = null;
    public function __construct($authorization = null, $signInfo = null)
    {
      $this->authorization = trim($authorization) ;
      $this->signInfo = $signInfo ;
      // 检测基本数据
      $this->checkBaseData();
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
    protected function getAuthData($sessionId)
    {
      return array(
        'card'=>'ed9a-d251b2e6-48c3-9c08-e426-ed15398ac305-73624bb2',
        'key'=>'c4ba-ae8878c1641b-270a-073bb98e-cc54-1590-2a48-79a304e5a6cb-9dda07f2-1d03eef14b56-29d0-5a14db07-abf6'
      );
    }

    //在uri编码中不能对'/'编码
    public function runSign()
    {
      return $this->sign();
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
        $result .= $PERCENT_ENCODED_STRINGS[ord($value[$i])];
      }
      return $result;
    }
  }

 ?>