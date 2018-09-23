<?php
/**
 * Created by PhpStorm.
 * User: sicmouse
 * Date: 2018/9/1
 * Time: 下午6:22
 */

namespace DdvPhp\DdvRestfulApi\Middleware\Laravel;

use Closure;
use Illuminate\Http\Request;
use DdvPhp\DdvRestfulApi\Lib\HttpRequestStream;
use DdvPhp\DdvRestfulApi\Exception\RequestParseError;
use DdvPhp\DdvRestfulApi\Interfaces\RequestInfo as RequestInfoInterface;

class RequestInfo
{

    /**
     * @param Request $request
     * @param Closure $next
     * @param null $config
     * @return mixed
     * @throws RequestParseError
     */
    public function handle(Request $request, Closure $next, $config = null)
    {
        self::createHttpRequestInfo($request);
        return $next($request);;
    }

    /**
     * @param Request $request
     * @throws RequestParseError
     */
    public static function createHttpRequestInfo(Request $request)
    {
        $isCompleted = false;
        if (isset($request->ddvHttpRequestInfo) && $request->ddvHttpRequestInfo instanceof RequestInfoInterface) {
            return;
        }
        // 获取ip
        $remoteAddress = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '';
        $remotePort = isset($_SERVER['REMOTE_PORT']) ? $_SERVER['REMOTE_PORT'] : '0';
        $requestStream = new HttpRequestStream();
        $requestStream
            ->baseInit(array(), $_SERVER)
            ->setRemoteInfo($remoteAddress, $remotePort, array(
                'remoteIp' => $request->getClientIp(),
                'remoteAddress' => $remoteAddress,
                'remotePort' => $remotePort
            ))
            // 解析完成
            ->onRequested(function (RequestInfoInterface $requestInfo) use (&$request, &$isCompleted) {
                $request->ddvHttpRequestInfo = $requestInfo;
                $content = null;
                $request->initialize($requestInfo->getQuerys(), $requestInfo->getParameters(), array(), $requestInfo->getCookies(), $requestInfo->getFiles(), $requestInfo->getServers(), $content);
                $request->ddvHttpRequestInfo = $requestInfo;
                $isCompleted = true;
                // 释放内存
                unset($request, $content, $isCompleted);
            });
        $fp = fopen('php://input', 'rb');
        while (!feof($fp)) {
            $requestStream->write(fread($fp, 512));
        }
        fclose($fp);
        // 判断是否有响应
        if (!$isCompleted || !($request->ddvHttpRequestInfo instanceof RequestInfoInterface)) {
            throw new RequestParseError('ddvHttpRequestInfo Error', 'DDV_HTTP_REQUEST_INFO_ERROR', 500);
        }
    }
}
