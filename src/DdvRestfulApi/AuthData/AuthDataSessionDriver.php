<?php
namespace DdvPhp\DdvRestfulApi\AuthData;

use Mockery\CountValidator\Exception;
/**
* 
*/
class AuthDataSessionDriver extends \DdvPhp\DdvRestfulApi\AuthData\AuthDataDriver implements \DdvPhp\DdvRestfulApi\AuthData\AuthDataHandlerInterface
{
  public function __construct()
  {
    parent::__construct();
  }
  public function open($authDataDriverConfig = null)
  {
    return $this->_success;
  }
  public function read($sessionId)
  {
    $sessionIdOld = @session_id();
    $sessionDataOld = empty($_SESSION)?null:$_SESSION;
    //关闭会话
    @session_write_close();
    //清除数据
    @session_unset();
    //空会话
    @$_SESSION = array();
    // 
    @session_id($sessionId);
    //
    @session_start();
    $authData = isset($_SESSION['__ddvAuthData__']) ? $_SESSION['__ddvAuthData__'] : null;
    //关闭会话
    function_exists('session_abort') ? @session_abort() : @session_write_close();
    //清除数据
    @session_unset();
    //清理头
    @header_remove('Set-Cookie');
    //空会话
    @$_SESSION = array();
    // 
    @session_id($sessionIdOld);
    //
    @session_start();

    if (!empty($sessionDataOld)) {
      $_SESSION = $sessionDataOld;
    }
    return $authData;
  }
  public function write($sessionId, $authData)
  {
    $sessionIdOld = @session_id();
    $sessionDataOld = empty($_SESSION)?null:$_SESSION;
    //关闭会话
    function_exists('session_abort') ? @session_abort() : @session_write_close();
    //清除数据
    @session_unset();
    //空会话
    @$_SESSION = array();
    // 
    @session_id($sessionId);
    //
    @session_start();
    $_SESSION['__ddvAuthData__'] = $authData;
    //关闭会话
    @session_write_close();
    //清除数据
    @session_unset();
    //清理头
    @header_remove('Set-Cookie');
    //空会话
    @$_SESSION = array();
    // 
    @session_id($sessionIdOld);
    //
    @session_start();

    if (!empty($sessionDataOld)) {
      $_SESSION = $sessionDataOld;
    }
    return $this->_success;
  }
  public function close()
  {
    return $this->_success;
  }
  public function destroy($sessionId)
  {
    return $this->_success;
  }
  public function gc($maxlifetime)
  {
    return $this->_success;
  }
}