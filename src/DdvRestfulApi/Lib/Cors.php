<?php
/**
 * https://developer.mozilla.org/zh-CN/docs/Glossary/CORS
 * Created by PhpStorm.
 * User: hua
 * Date: 2018/9/3
 * Time: 上午10:11
 */

namespace DdvPhp\DdvRestfulApi\Lib;

use DdvPhp\DdvRestfulApi\Exception\RJsonError;
use DdvPhp\DdvRestfulApi\Interfaces\Cors as CorsInterface;
use DdvPhp\DdvRestfulApi\Interfaces\RequestInfo;

class Cors implements CorsInterface
{
    /**
     * @var RequestInfo|null
     */
    protected $requestInfo = null;
    /**
     * 返回结果可以用于缓存的最长时间，单位是秒。
     * 在Firefox中，上限是24小时 （即86400秒），
     * 而在Chromium 中则是10分钟（即600秒）。
     * Chromium 同时规定了一个默认值 5 秒。
     * 如果值为 -1，则表示禁用缓存，
     * 每一次请求都需要提供预检请求，
     * 即用OPTIONS请求进行检测。
     * @var int $allowControlMaxAge
     */
    protected $allowControlMaxAge = 7200;
    /**
     * 所有需要授权的origin
     * @var array
     */
    protected $allowOrigins = array();
    /**
     * 所有需要授权的method
     * @var array
     */
    protected $allowMethods = array();
    /**
     * 请求源是否通过
     * @var bool
     */
    protected $isAllowOriginPass = false;
    /**
     * 是否仅仅返回头
     * @var bool
     */
    protected $isResponseOnlyHeader = false;
    /**
     * 所有需要授权的headers
     * @var array
     */
    protected $allowOriginRequestHeaders = array();
    protected $allowOriginExposeHeaders = array();
    /**
     * 响应头
     * @var array
     */
    protected $responseHeaders = array();
    protected $methods = array();

    protected $allowHeader = array();
    protected $method = 'GET';
    protected $originMethod = 'GET';
    protected $httpAccessControlReqyestMehtod = null;
    protected $originHeadersStr = '';

    public function checkAllow()
    {
        // 获取授权头
        $origin = $this->requestInfo->getHeader('origin');
        if (empty($origin)) {
            return false;
        }
        /**
         * 判断请求源是否在授权内
         */
        foreach ($this->allowOrigins as $origint) {
            // 如果开始值就是请求源，通过
            if ($origin === substr($origint, 0, strlen($origin))) {
                // 通过
                $this->isAllowOriginPass = true;
                break;
            } else if (preg_match(('/^' . self::getReg($origint) . '$/'), $origin)) {
                // 使用正则再次判断 - 通过
                $this->isAllowOriginPass = true;
                break;
            }
        }
        /**
         * 通过就需要输出通过的头
         */
        /**
         * 响应头表示是否可以将对请求的响应暴露给页面。返回true则可以，其他值均不可以。
         */
        $this->header('Access-Control-Allow-Credentials', ($this->isAllowOriginPass ? 'true' : 'false'));
        if ($this->isAllowOriginPass) {
            // 响应头指定了该响应的资源是否被允许与给定的origin共享
            $this->header('Access-Control-Allow-Origin', $origin);
        } else {
            throw new RJsonError('No origin is allowed', 'NO_ORIGIN_ALLOWED');
        }
        /**
         * 请求Method
         * @var string|null $method
         */
        $method = $this->requestInfo->getMethod();
        /**
         * 请求源Method
         * @var string|null $originMethod
         */
        $originMethod = $this->requestInfo->getHeader('Access-Control-Request-Method');
        /**
         * 请求源方式如果为空，没法后续授权了
         */
        if (empty($originMethod)) {
            return $this->isAllowOriginPass;
        } elseif (in_array(strtoupper($originMethod), $this->allowMethods)) {
            $this->header('Access-Control-Allow-Methods', $originMethod);
        } else {
            throw new RJsonError('No method is allowed', 'NO_METHODS_ALLOWED');
        }
        /**
         * 请求方式不为OPTIONS
         * 也就是说，去具体请求了，就不需要更多的描述了
         * https://developer.mozilla.org/zh-CN/docs/Glossary/CORS
         */
        if ($method === 'OPTIONS') {
            $this->isResponseOnlyHeader = true;
        } else {
            return $this->isAllowOriginPass;
        }
        /**
         * 请求源Headers
         * @var string|null $originHeaders
         */
        $originHeadersStr = $this->requestInfo->getHeader('Access-Control-Request-Headers');
        /**
         * 请求源Headers
         * @var string|null $originHeaders
         */
        $originHeaders = explode(',', empty($originHeadersStr) ? '' : $originHeadersStr);
        /**
         * @var array $allowOriginHeaders
         */
        $allowOriginHeaders = array();
        foreach ($originHeaders as $header) {
            // 去除头的空格
            $header = trim($header);

            if (!$this->checkAllowHeader($header)) {
                throw new RJsonError('No ' . $header . ' header is allowed', 'NO_HEADER_ALLOWED');
            }
            $allowOriginHeaders[] = $header;
        }
        /**
         * 授权的Headers
         * @var string|null $allowOriginHeadersStr
         */
        $allowOriginHeadersStr = implode(', ', $allowOriginHeaders);
        //允许自定义的头部，以逗号隔开，大小写不敏感
        $this->header('Access-Control-Allow-Headers', $allowOriginHeadersStr);
        /**
         * 授权的Headers
         * @var string|null $allowOriginExposeHeadersStr
         */
        $allowOriginExposeHeadersStr = implode(', ', $this->allowOriginExposeHeaders);
        //允许脚本访问的返回头，请求成功后，脚本可以在XMLHttpRequest中访问这些头的信息(貌似webkit没有实现这个)
        $this->header('Access-Control-Expose-Headers', $allowOriginExposeHeadersStr);
        //缓存此次请求的秒数。在这个时间范围内，所有同类型的请求都将不再发送预检请求而是直接使用此次返回的头作为判断依据，非常有用，大幅优化请求次数
        $this->header('Access-Control-Max-Age', $this->allowControlMaxAge);

        return $this->isAllowOriginPass;
    }

    /**
     * 所有需要返回的头数组
     * @return array
     */
    public function getResponseHeaders()
    {
        return empty($this->responseHeaders) ? array() : $this->responseHeaders;
    }

    /**
     * 是否仅仅返回头
     * @return bool
     */
    public function isResponseOnlyHeader()
    {
        return (boolean)$this->isResponseOnlyHeader;
    }

    public function setConfig($config = array())
    {
        if (empty($config) || (!is_array($config))) {
            throw new RJsonError('Config must be an array', 'CONFIG_MUST_BE_AN_ARRAY');
        }
        $this->allowOrigins = (!empty($config['origin'])) && is_array($config['origin']) ? $config['origin'] : array();
        $this->allowMethods = (!empty($config['method'])) && is_array($config['method']) ? $config['method'] : array();
        $this->allowControlMaxAge = (!empty($config['control'])) && is_numeric($config['control']) ? $config['control'] : 7200;
        $this->allowOriginRequestHeaders = (!empty($config['allowHeader'])) && is_array($config['allowHeader']) ? $config['allowHeader'] : array();
        $this->allowOriginExposeHeaders = array('set-cookie', 'request-id', 'session-sign');

        return $this;
    }

    /**
     * 设置请求信息
     * @param RequestInfo $requestInfo
     * @return $this
     * @throws RJsonError
     */
    public function setRequestInfo(RequestInfo $requestInfo)
    {
        if (isset($requestInfo) && $requestInfo instanceof RequestInfo) {
            $this->requestInfo = $requestInfo;
        } else {
            throw new RJsonError('requestInfo is wrong');
        }
        return $this;
    }

    protected function checkAllowHeader($originHeader = '')
    {
        $allowHeaderPass = false;
        foreach ($this->allowOriginRequestHeaders as $header) {
            if ($originHeader === substr($header, 0, strlen($originHeader))) {
                $allowHeaderPass = true;
                break;
            } else if (preg_match(('/^' . $this->getReg($header) . '$/i'), $originHeader)) {
                $allowHeaderPass = true;
                break;
            }
        }
        return $allowHeaderPass;
    }

    protected function getReg($url = '')
    {
        $reg = preg_replace_callback(
            '([\*\.\?\+\$\^\[\]\(\)\{\}\|\\\/])',
            function ($matches) {
                if ($matches[0] === '*') {
                    return '(.*)';
                } else {
                    return '\\' . $matches[0];
                }
            },
            $url
        );
        return $reg;
    }

    protected function header($key, $values)
    {
        $this->responseHeaders[$key] = $values;
    }
}
