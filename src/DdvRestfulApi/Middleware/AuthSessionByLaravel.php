<?php

namespace DdvPhp\DdvRestfulApi\Middleware;
use Closure;
use const null;
use const true;
use const false;

class AuthSessionByLaravel extends \Illuminate\Session\Middleware\StartSession
{
  /**
   * Handle an incoming request.
   *
   * @param  \Illuminate\Http\Request  $request
   * @param  \Closure  $next
   * @param  string|null  $guard
   * @return mixed
   */
  public function handle($request, Closure $next, $guard = null)
  {
    if(IsDdvRestfulApiAuth::$state){
      return $next($request);
    }
    $ddvRestfulApi = \DdvPhp\DdvRestfulApi::getInstance();
    $ddvRestfulApi->config(array('authDataDriver'=>AuthDataSessionDriver::class));
    IsDdvRestfulApiAuth::$state = true;

    $sessionName = config('session.cookie');

    AuthDataSessionDriver::$inputRequest = $request;
    AuthDataSessionDriver::$inputSession = $this;
    AuthDataSessionDriver::$inputSessionName = $sessionName;


    // 使用 ddvRestfulApi 授权签名
    $ddvRestfulApi->authSign();

    $sessionId = $ddvRestfulApi->getSessionId();
    $sessionId = strlen($sessionId)===40?$sessionId:$sessionId.'88888888';


    try{
      // 直接通过cookie 数组 重写 $sessionId
      $_COOKIE[$sessionName] = $sessionId;
    }catch(\Exception $e){}
    try{
      // 通过请求对象来重写 $sessionId
      $request->cookies->set($sessionName, $sessionId);
    }catch(\Exception $e){}
    $this->getSession($request)->setId($sessionId);
    // 开启会话
    $response = parent::handle($request, function ($request) use ($next) {
      return $next($request);
    });

    try{
      // 通过请求对象来清理cookie
      $request->cookies->remove(config('session.cookie'));
    }catch(\Exception $e){}
    try{
      // 通过返回对象来清理cookie
      $response->headers->removeCookie(config('session.cookie'));
    }catch(\Exception $e){}

    return $response;
  }
}

class IsDdvRestfulApiAuth{
  public static $state = false;
}

class AuthDataSessionDriver extends \DdvPhp\DdvRestfulApi\AuthData\AuthDataDriver implements \DdvPhp\DdvRestfulApi\AuthData\AuthDataHandlerInterface
{
  public static $inputRequest = null;
  public static $inputSession = null;
  public static $inputSessionName = null;
  public $request = null;
  public $session = null;
  public $startSession = null;
  public $sessionName = null;
  public function __construct()
  {
    parent::__construct();
  }
  public function open($authDataDriverConfig = null)
  {
    $className =  get_called_class();
    $this->request = $className::$inputRequest;
    $this->startSession = $className::$inputSession;
    $this->sessionName = $className::$inputSessionName;
    return $this->_success;
  }
  public function read($sessionId)
  {
    $sessionId = strlen($sessionId)===40?$sessionId:$sessionId.'88888888';
    try{
      // 直接通过cookie 数组 重写 $sessionId
      $_COOKIE[$this->sessionName] = $sessionId;
    }catch(\Exception $e){}
    try{
      // 通过请求对象来重写 $sessionId
      $this->request->cookies->set($this->sessionName, $sessionId);
    }catch(\Exception $e){}
    $session = $this->startSession->getSession($this->request);
    $session->setId($sessionId);
    $session->start();
    $authData = $session->get('__ddvAuthData__');
    return $authData;
  }
  public function write($sessionId, $authData)
  {
    $sessionId = strlen($sessionId)===40?$sessionId:$sessionId.'88888888';
    try{
      // 直接通过cookie 数组 重写 $sessionId
      $_COOKIE[$this->sessionName] = $sessionId;
    }catch(\Exception $e){}
    try{
      // 通过请求对象来重写 $sessionId
      $this->request->cookies->set($this->sessionName, $sessionId);
    }catch(\Exception $e){}
    $session = $this->startSession->getSession($this->request);
    $session->setId($sessionId);
    $session->put('__ddvAuthData__', $authData);
    $session->save();
    return $this->_success;
  }
  public function generateSessionId()
  {
    $session = $this->startSession->getSession($this->request);
    $session->setId(bin2hex(random_bytes(40)));
    return $session->getId();
  }
  public function close()
  {
    $this->request = null;
    $this->startSession = null;
    $this->sessionName = null;
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
