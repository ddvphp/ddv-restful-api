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
            $restfulApi = \DdvPhp\DdvRestfulApi::getInstance();
            // 配置 ddvRestfulApi 参数
            $restfulApi->config((config('ddvRestfulApi')));
            // 使用 ddvRestfulApi 解析请求数据
            $restfulApi->requestParse();
            // 使用 ddvRestfulApi 跨越
            $restfulApi->initCors();
        }
        $response = $next($request);
        
        $r = array_merge($restfulApi->responseData, $response->original);

        if (empty($r['statusCode'])) {
            if (method_exists($response, 'getStatusCode')) {
                $r['statusCode'] = $response->getStatusCode();
            }
        }
        if (empty($r['errorId'])) {
            $r['errorId'] = empty($response->statusTexts[$r['statusCode']])? $r['message'] : $response->statusTexts[$r['statusCode']];
        }
        $response->original = $r ;
        $response->setStatusCode($r['statusCode'], $r['message']);
        $response->setContent($restfulApi->echoStr($r, false, false, false, false));
        return $response;
    }
}