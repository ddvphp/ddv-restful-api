<?php
/**
 * Created by PhpStorm.
 * User: sicmouse
 * Date: 2018/9/2
 * Time: 上午12:08
 */

namespace DdvPhp\DdvRestfulApi\Middleware\Laravel;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use DdvPhp\DdvRestfulApi\Lib\Cors as CorsLib;

class Cors
{
    public function handle(Request $request, Closure $next)
    {
        if (empty($request->ddvHttpRequestInfo) || (!($request->ddvHttpRequestInfo instanceof RequestInfoInterface))) {
            RequestInfo::createHttpRequestInfo($request);
        }
        $cors = new CorsLib();
        $cors->setRequestInfo($request->ddvHttpRequestInfo);
        $cors->setConfig(config('ddvRestfulApi.cors'));
        if ($cors->checkAllow()) {
            $response = $cors->isResponseOnlyHeader() ? new Response() : $next($request);
            foreach ($cors->getResponseHeaders() as $key => $value) {
                $response->header($key, $value);
            }
            return $response;
        }
        return $next($request);
    }
}
