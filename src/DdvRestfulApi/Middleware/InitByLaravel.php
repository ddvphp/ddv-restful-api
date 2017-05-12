<?php

namespace DdvPhp\DdvRestfulApi\Middleware;

use Closure;

class InitByLaravel
{
    protected $isDdvRestfulApiInit = false;
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
        if(!$this->isDdvRestfulApiInit){
            $this->isDdvRestfulApiInit = true;
            $restfulApi = \DdvPhp\DdvRestfulApi\DdvRestfulApi::getInstance();
            // 配置 ddvRestfulApi 参数
            $restfulApi->config((config('ddvRestfulApi')));
            // 使用 ddvRestfulApi 解析请求数据
            $restfulApi->requestParse();
            // 使用 ddvRestfulApi 跨越
            $restfulApi->initCors();
        }
        return $next($request);
    }
}