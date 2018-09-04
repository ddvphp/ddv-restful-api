<?php
/**
 * Created by PhpStorm.
 * User: sicmouse
 * Date: 2018/9/1
 * Time: 下午7:08
 */

namespace DdvPhp\DdvRestfulApi\Middleware\Laravel;

use Closure;
use Illuminate\Http\Request;
use DdvPhp\DdvRestfulApi\Lib\Auth;
use DdvPhp\DdvRestfulApi\Middleware\Laravel\RequestInfo;
use DdvPhp\DdvRestfulApi\Lib\RequestSignInfo;
use DdvPhp\DdvRestfulApi\Interfaces\RequestInfo as RequestInfoInterface;
use DdvPhp\DdvRestfulApi\AuthData\AuthDataSessionLaravelDriver;

class AuthSession extends \Illuminate\Session\Middleware\StartSession
{

    public function handle($request, Closure $next)
    {
        if (empty($request->ddvHttpRequestInfo) || (!($request->ddvHttpRequestInfo instanceof RequestInfoInterface))) {
            RequestInfo::createHttpRequestInfo($request);
        }
        /**
         * @var RequestInfoInterface $requestInfo
         */
        $requestInfo = $request->ddvHttpRequestInfo;
        /**
         * @var RequestSignInfo $requestSignInfo
         */
        $requestSignInfo = new RequestSignInfo();
        /**
         * 设置请求信息
         */
        $requestSignInfo->createRequestInfo($requestInfo);
        $requestSignInfo->setHeadersPrefix('x-ddv-');

        $sessionName = config('session.cookie');
        $auth = new Auth();
        $auth->setRequestSignInfo($requestSignInfo);
        $auth->setConfig(array(
                'authDataDriver' => AuthDataSessionLaravelDriver::class,
                'authDataDriverConfig' => array(
                    'request' => $request,
                    'session' => $this,
                    'sessionName' => $sessionName,
                )
            )
        );
        $auth->sign();

        $sessionId = $auth->getAccessKeyId();
        $sessionId = strlen($sessionId) === 40 ? $sessionId : $sessionId . '88888888';


        try {
            // 直接通过cookie 数组 重写 $sessionId
            $_COOKIE[$sessionName] = $sessionId;
        } catch (\Exception $e) {
        }
        try {
            // 通过请求对象来重写 $sessionId
            $request->cookies->set($sessionName, $sessionId);
        } catch (\Exception $e) {
        }
        // 获取会话
        $session = $this->getSession($request);
        // 设置会话id
        $session->setId($sessionId);
        // 开启会话
        $response = parent::handle($request, function ($request) use ($next) {
            return $next($request);
        });
        // 保存会话
        $session->save();

        try {
            // 通过请求对象来清理cookie
            $request->cookies->remove(config('session.cookie'));
        } catch (\Exception $e) {
        }
        try {
            // 通过返回对象来清理cookie
            $response->headers->removeCookie(config('session.cookie'));
        } catch (\Exception $e) {
        }

        return $response;
    }
}

