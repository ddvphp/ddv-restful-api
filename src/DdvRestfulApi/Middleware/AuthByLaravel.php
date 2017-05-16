<?php

namespace DdvPhp\DdvRestfulApi\Middleware;
use Closure;

class AuthByLaravel
{
  protected $isDdvRestfulApiAuth = false;
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
    if(!$this->isDdvRestfulApiAuth){
      $this->isDdvRestfulApiAuth = true;
      $restfulApi = \DdvPhp\DdvRestfulApi::getInstance();

      $restfulApi->config(array('authDataDriver'=>'session'));
      
      function_exists('session_abort') ? @session_abort() : @session_write_close();
      // 使用 ddvRestfulApi 授权签名
      try {
        $restfulApi->authSign();
      } catch (Exception $e) {
        @header_remove('Set-Cookie');
        throw $e;
      }
      //关闭会话
      function_exists('session_abort') ? @session_abort() : @session_write_close();
      //清除数据
      @session_unset();
      $session_id = $restfulApi->getSessionId();
      @$_COOKIE[$request->session()->getName()] = $session_id;
      // 开启会话
      @session_id($session_id);
      // 开启会话
      @session_start();
      @header_remove('Set-Cookie');
    }
    return $next($request);
  }
}