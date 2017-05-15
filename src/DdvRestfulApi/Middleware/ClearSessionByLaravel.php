<?php

namespace DdvPhp\DdvRestfulApi\Middleware;
use Closure;

class ClearSessionByLaravel
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
    $_COOKIE[config('session.cookie')] = 'ddvrestful2ddvrestful2ddvrestful';
    // 开启会话
    @session_id('ddvrestful2ddvrestful2ddvrestful');
    return $next($request);
  }
}