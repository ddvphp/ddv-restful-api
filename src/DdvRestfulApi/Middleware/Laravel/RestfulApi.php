<?php
/**
 * Created by PhpStorm.
 * User: sicmouse
 * Date: 2018/9/1
 * Time: 下午7:08
 */

namespace DdvPhp\DdvRestfulApi\Middleware\Laravel;

use Closure;
use Illuminate\Http\Response;
use Symfony\Component\Debug\Exception\FlattenException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use DdvPhp\DdvRestfulApi;
use DdvPhp\DdvRestfulApi\Lib\HttpRequestStream;
use Illuminate\Http\Request;

class RestfulApi
{
    public function handle(Request $request, Closure $next, $guard = null)
    {
        if (empty($request->ddvHttpRequestInfo) || (!($request->ddvHttpRequestInfo instanceof RequestInfoInterface))) {
            RequestInfo::createHttpRequestInfo($request);
        }
        if (!self::isRender($request)) {
            return $next($request);
        }

        $r = array(
            //错误识别码
            'errorId' => 'OK',
            //消息
            'message' => '',
            //代码
            'code' => 0,
            //数据
            'data' => null,
            //列表
            'lists' => array(),
            //分页
            'page' => null
        );

        /**
         * @var Response $response
         */
        $response = $next($request);
        if (isset($response->original)) {
            if (class_exists('DdvPhp\DdvPage')) {
                // 分页
                if (class_exists('Illuminate\Pagination\LengthAwarePaginator') && $response->original instanceof \Illuminate\Pagination\LengthAwarePaginator) {
                    $response->original = new \DdvPhp\DdvPage($response->original);
                }
                // 如果是\DdvPhp\DdvPage
                if ($response->original instanceof \DdvPhp\DdvPage) {
                    $response->original = $response->original->toArray();
                }
            }

            $content = $response->getContent();
            if (is_array($response->original)) {
                $r = array_merge($r, $response->original);
            } else if (is_string($response->original)) {
                try {
                    $r = array_merge($r, json_decode($response->original, true));
                } catch (\Exception $e) {
                    return $response;
                }
            } else {
                try {
                    $r = array_merge($r, json_decode((string)$response->original, true));
                } catch (\Exception $e) {
                    try {
                        $r = array_merge($r, json_decode((string)$content, true));
                    } catch (\Exception $e) {
                        if (!empty($response->original)) {
                            return $response;
                        }
                    }
                }
            }
        }
        if (empty($r)) {
            return $response;
        }

        if (empty($r['statusCode'])) {
            if (method_exists($response, 'getStatusCode')) {
                $r['statusCode'] = $response->getStatusCode();
            }
        }
        if (empty($r['errorId'])) {
            $r['errorId'] = empty($response->statusTexts[$r['statusCode']]) ? $r['message'] : $response->statusTexts[$r['statusCode']];
        }
        $r['data'] = empty($r['data']) ? (object)array() : $r['data'];
        $r['page'] = empty($r['page']) ? (object)array() : $r['page'];

        $response->original = $r;
        $response->setStatusCode($r['statusCode'], $r['message']);
        $response->setContent(json_encode($r));
        $response->header('content-type', 'application/json', true);
        return $response;
    }

    public static function isDebug()
    {
        $isDebug = defined('ENVIRONMENT') && ENVIRONMENT === 'development';
        $isDebug = $isDebug || (function_exists('config') && config('app.debug', false)) || (function_exists('env') && env('APP_DEBUG'));
        return $isDebug;
    }

    protected static function isHas($str, $find)
    {
        return strpos($str, $find) !== false;
    }

    /**
     * RenderByLaravel an exception into an HTTP response.
     *
     * @param  \Illuminate\Http\Request $request
     * @return boolean
     */
    public static function isRender($request)
    {
        return true;
        $accept = $request->header('accept') ?? '';
        if (static::isHas($accept, 'json') || static::isHas($accept, 'api')) {
            return true;
        } else if (static::isHas($accept, 'html') || static::isHas($accept, 'xml') || static::isHas($accept, 'text')) {
            return false;
        } else if ($request->header('x-ddv-restful-api')) {
            return true;
        } else if ($request->header('authorization')) {
            foreach ($request->headers->keys() as $value) {
                if (static::isHas($accept, 'x-ddv-')) {
                    return true;
                }
            }
        }
        return false;
    }

    public static function convertExceptionToJsonResponse($exception)
    {
        $flattenException = FlattenException::create($exception);
        $code = $flattenException->getCode();
        if (method_exists($exception, 'getErrorId')) {
            $errorId = $exception->getErrorId();
        } else {
            $errorId = empty(Response::$statusTexts[$code]) ? 'UNKNOWN_ERROR' : Response::$statusTexts[$code];
        }
        if ($exception instanceof HttpExceptionInterface) {
            $statusCode = $flattenException->getStatusCode();
        } else if (isset(Response::$statusTexts[$code])) {
            $statusCode = $code;
        }
        if (empty($statusCode)) {
            $statusCode = empty($code) ? 500 : $code;
        }
        if (!isset(Response::$statusTexts[$code]) && $errorId) {
            $code = $statusCode;
            Response::$statusTexts[$statusCode] = $errorId;
            $statusCode = 500;
        }
        $r = [
            'statusCode' => $statusCode,
            'code' => $code,
            'errorId' => $errorId,
            'message' => $flattenException->getMessage()
        ];
        if (static::isDebug()) {
            $r['debug'] = [
                'type' => $flattenException->getClass(),
                'class' => $flattenException->getClass(),
                'line' => $flattenException->getLine(),
                'file' => $flattenException->getFile(),
                'trace' => $flattenException->getTrace(),
                'isError' => $exception instanceof \ErrorException,
                'isIgnoreError' => false,
            ];
        }
        if (method_exists($exception, 'getResponseData')) {
            $r = array_merge($exception->getResponseData(), $r);
        }

        $response = new Response();
        $response->setStatusCode($statusCode, $errorId);
        foreach ($flattenException->getHeaders() as $key => $values) {
            $response->headers->set($key, $values);
        }
        $response->setContent($r);
        return $response;
    }

}
