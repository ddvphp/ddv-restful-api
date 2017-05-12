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
            \DdvPhp\DdvRestfulApi\DdvRestfulApi::getInstance()->authSign();
            $this->isDdvRestfulApiAuth = true;
        }
        return $next($request);
    }
}