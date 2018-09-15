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
use Carbon\Carbon;
use Illuminate\Support\Arr;
use Illuminate\Session\SessionManager;
use Illuminate\Contracts\Session\Session;
use Illuminate\Session\CookieSessionHandler;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\Response;

class AuthSession
{

    /**
     * 会话管理对象
     *
     * @var \Illuminate\Session\SessionManager
     */
    protected $manager;

    /**
     * 指示是否为当前请求处理了会话。
     *
     * @var bool
     */
    protected $sessionHandled = false;

    /**
     * 创建一个新的会话中间件。
     *
     * @param  \Illuminate\Session\SessionManager  $manager
     * @return void
     */
    public function __construct(SessionManager $manager)
    {
        $this->manager = $manager;
    }

    /**
     * 处理传入的请求。
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
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

        //$config = $this->manager->getSessionConfig();
        // 过去时间
        //return $config['expire_on_close'] ? 0 : Carbon::now()->addMinutes($config['lifetime']);

        $sessionName = config('session.cookie');
        $auth = new Auth();
        $auth->setRequestSignInfo($requestSignInfo);
        $auth->setConfig(array(
                'authDataDriver' => AuthDataSessionLaravelDriver::class,
                'authDataDriverConfig' => array(
                    'session' => $this
                )
            )
        );
        $auth->sign();

        $sessionId = $auth->getAccessKeyId();
        $sessionId = strlen($sessionId) === 40 ? $sessionId : $sessionId . '88888888';
        
        
        // 开启会话
        
        $this->sessionHandled = true;
        echo "\n22*999+9\n";
        var_dump($this->sessionHandled);

        // 如果已配置会话驱动程序，我们需要在此处启动会话
        // 以便数据为应用程序做好准备。 请注意Laravel会话
        // 不要以任何方式使用PHP“本机”会话，因为它们很糟糕。
        if ($this->sessionConfigured()) {
            $request->setLaravelSession(
                $session = $this->startSession($request, $sessionId)
            );

            $this->collectGarbage($session);
        }

        // 保存会话
        $session->save();
        // 开启项目
        $response = $next($request);
        // 保存会话
        $session->save();



        return $response;
    }

    /**
     * 执行请求生命周期的任何最终操作。
     * Perform any final actions for the request lifecycle.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Symfony\Component\HttpFoundation\Response  $response
     * @return void
     */
    public function terminate($request, $response)
    {
            echo "2232322\n";
            var_dump($this->sessionHandled, $request->manager);
        if ($this->sessionHandled && $this->sessionConfigured()) {
            echo "2232322";
            $this->manager->driver()->save();
        }
    }

    /**
     * Start the session for the given request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Contracts\Session\Session
     */
    protected function startSession(Request $request, $sessionId)
    {
        return tap($this->getSession($sessionId), function ($session) use ($request) {
            $session->setRequestOnHandler($request);
            $session->start();
        });
    }

    /**
     * Get the session implementation from the manager.
     *
     * @param  string  $sessionId
     * @return \Illuminate\Contracts\Session\Session
     */
    public function getSession($sessionId)
    {
        return tap($this->manager->driver(), function ($session) use ($sessionId) {
            $session->setId($sessionId);
        });
    }

    /**
     * 如有必要，从会话中删除垃圾。
     *
     * @param  \Illuminate\Contracts\Session\Session  $session
     * @return void
     */
    protected function collectGarbage(Session $session)
    {
        $config = $this->manager->getSessionConfig();

        // 在这里，我们将通过命中在任何给定请求上执行垃圾收集所需的几率来查看此请求是否达到垃圾收集抽奖。
        // 如果我们点击它，
        // 我们将调用此处理程序让它删除所有过期的会话。
        if ($this->configHitsLottery($config)) {
            $session->getHandler()->gc($this->getSessionLifetimeInSeconds());
        }
    }

    /**
     * Determine if the configuration odds hit the lottery.
     * 确定配置赔率是否达到了彩票。
     *
     * @param  array  $config
     * @return bool
     */
    protected function configHitsLottery(array $config)
    {
        return random_int(1, $config['lottery'][1]) <= $config['lottery'][0];
    }

    /**
     * Get the session lifetime in seconds.
     * 以秒为单位获取会话生存期。
     *
     * @return int
     */
    protected function getSessionLifetimeInSeconds()
    {
        return Arr::get($this->manager->getSessionConfig(), 'lifetime') * 60;
    }

    /**
     * 确定是否已配置会话驱动程序。
     * Determine if a session driver has been configured.
     *
     * @return bool
     */
    protected function sessionConfigured()
    {
        return ! is_null(Arr::get($this->manager->getSessionConfig(), 'driver'));
    }

    /**
     * 确定配置的会话驱动程序是否持久。
     * Determine if the configured session driver is persistent.
     *
     * @param  array|null  $config
     * @return bool
     */
    protected function sessionIsPersistent(array $config = null)
    {
        $config = $config ?: $this->manager->getSessionConfig();

        return ! in_array($config['driver'], [null, 'array']);
    }
}

