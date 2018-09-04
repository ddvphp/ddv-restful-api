<?php
/**
 * Created by PhpStorm.
 * User: sicmouse
 * Date: 2018/9/4
 * Time: 下午2:52
 */

namespace DdvPhp\DdvRestfulApi\Lib;

use DdvPhp\DdvRestfulApi\Util\ResponseParse;
use DdvPhp\DdvRestfulApi\Interfaces\RestfulApiEcho as RestfulApiEchoInterface;
use DdvPhp\DdvRestfulApi\Interfaces\RequestInfo as RequestInfoInterface;

class RestfulApiEcho extends RestfulApi implements RestfulApiEchoInterface
{
    /**
     * @var array 
     */
    public $responseData = array(
        //错误识别码
        'errorId'=>'OK',
        //消息
        'message'=>'',
        //代码
        'code'=>0,
        //数据
        'data'=>null,
        //列表
        'lists'=>array(),
        //分页
        'page'=>null
    );
    /**
     * @var array
     */
    protected $corsConfig = array();

    /**
     * [onHandler 监听错误]
     * @author: 桦 <yuchonghua@163.com>
     * @DateTime 2017-04-26T18:55:58+0800
     * @return   [type]                   [description]
     */
    public function onHandler($r, $e)
    {
        if (isset($r['isIgnoreError']) && $r['isIgnoreError'] === true) {
            return;
        }
        if (!empty($r['responseData'])) {
            array_merge($r, $r['responseData']);
        }
        if (isset($r['responseData'])) unset($r['responseData']);
        if (!$this->isDevelopment()) {
            if (isset($r['debug'])) unset($r['debug']);
            if (isset($r['isIgnoreError'])) unset($r['isIgnoreError']);
        }
        return $this->echoStr($r);
    }

    public function isDevelopment()
    {
        $isDebug = defined('ENVIRONMENT') && ENVIRONMENT === 'development';
        $isDebug = $isDebug || function_exists('env') && env('APP_DEBUG');
        return $isDebug;
    }

    public function config($config = array())
    {
        if (!empty($config['cors'])){
            $this->corsConfig = $config['cors'];
        }
        if (!empty($config['headersPrefix'])){
            $this->authHeadersPrefix = $config['headersPrefix'];
        }
        $this->setConfig($config);
    }
    public function requestParse($parameters = null, $cookies = '', $files = null, $server = null, $headers = array())
    {
        $isCompleted = false;
        $requestInfo = false;
        // 获取ip
        $remoteAddress = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '';
        $remotePort = isset($_SERVER['REMOTE_PORT']) ? $_SERVER['REMOTE_PORT'] : '0';
        $requestStream = new HttpRequestStream();
        $requestStream
            ->baseInit(array(), $_SERVER)
            ->setRemoteInfo($remoteAddress, $remotePort, array(
                'remoteIp' => $remoteAddress,
                'remoteAddress' => $remoteAddress,
                'remotePort' => $remotePort
            ))
            // 解析完成
            ->onRequested(function (RequestInfoInterface $requestInfoInput) use (&$requestInfo, &$isCompleted) {
                $requestInfo = $requestInfoInput;
                $isCompleted = true;
                // 释放内存
                unset($isCompleted, $requestInfo, $requestInfoInput);
            });
        $fp = fopen('php://input', 'rb');
        while (!feof($fp)) {
            $requestStream->write(fread($fp, 512));
        }
        fclose($fp);
        // 判断是否有响应
        if (!$isCompleted || !($requestInfo instanceof RequestInfoInterface)) {
            throw new RequestParseError('ddvHttpRequestInfo Error', 'DDV_HTTP_REQUEST_INFO_ERROR', 500);
        }
        $this->setRequestInfo($requestInfo);
        unset($isCompleted, $requestInfo);
    }
    /**
     * [echoData 输出]
     * @author: 桦 <yuchonghua@163.com>
     * @DateTime 2017-04-26T18:56:12+0800
     */
    public function echoData($data)
    {
        $data = array_merge($this->responseData, $data);
        return $this->echoStr($data);
    }

    /**
     * [echo404 输出]
     * @author: 桦 <yuchonghua@163.com>
     * @DateTime 2017-04-26T18:56:12+0800
     */
    public function echo404($message = 'Api interface not found', $errorId = '404 Not Found', $statusCode = 404)
    {
        $responseData = array(
            'statusCode' => $statusCode,
            'code' => 0,
            'errorId' => $errorId,
            'message' => $message
        );
        return $this->echoData($responseData);
    }

    /**
     * [setHandler 设置错误监听]
     * @author: 桦 <yuchonghua@163.com>
     * @DateTime 2017-04-26T18:56:12+0800
     */
    public function useHandler()
    {
        \DdvPhp\DdvException\Handler::setHandler(function (array $r, $e) {
            $this->onHandler($r, $e);
        }, function () {
            return $this->isDevelopment();
        });
        return $this;
    }

    /**
     * @return RestfulApiInterface|RestfulApi
     */
    public function corsInit($config = array())
    {
        $this->corsConfig = array_merge($this->corsConfig, $config);
        $cors = new Cors();
        $cors->setRequestInfo($this->getRequestInfo());
        $cors->setConfig($this->corsConfig);
        if ($cors->checkAllow()) {
            foreach ($cors->getResponseHeaders() as $key => $value) {
                @header($key.': ' . $value);
            }
            if ($cors->isResponseOnlyHeader()){
                die;
            }
        }
    }
    public function initCors($config = array()){
        return $this->corsInit($config);
    }
    public function authSign(){
        return $this->sign();
    }

    /**
     * [setHandler 输出]
     * @author: 桦 <yuchonghua@163.com>
     * @DateTime 2017-04-26T18:56:12+0800
     */
    public function echoStr($data, $isEcho = true, $isAutoHeader = true, $isAutoSessionClose = true, $isAutoObClean = null, $isNotUnescapedUnicode = true)
    {
        // 关闭会话
        try {
            if ($isAutoSessionClose === true && function_exists('session_write_close')) {
                @session_write_close();
            }
        } catch (Exception $e) {
        }
        $isAutoObClean = !$this->isDevelopment();

        $statusCode = empty($data['statusCode']) ? (isset($data['errorId']) && $data['errorId'] !== 'OK' ? 500 : 200) : $data['statusCode'];
        $statusText = empty($data['errorId']) ? '' : $data['errorId'];
        $statusText = empty($statusText) ? (empty($data['statusText']) ? '' : $data['statusText']) : $statusText;
        $statusText = empty($statusText) ? (($statusCode >= 200 && $statusCode < 300) ? 'OK' : 'UNKNOWN_ERROR') : $statusText;
        if (function_exists('set_status_header')) {
            set_status_header($statusCode, $statusText);
        } else {
            try {
                //nginx模式
                if (strpos(PHP_SAPI, 'cgi') === 0) {
                    @header('Status: ' . $statusCode . ' ' . $statusText, TRUE);
                } else {
                    $serverProtocol = isset($_SERVER['SERVER_PROTOCOL']) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.1';
                    @header($serverProtocol . ' ' . $statusCode . ' ' . $statusText, TRUE, $statusCode);
                    unset($serverProtocol);
                }
            } catch (Exception $e) {
            }
        }
        if ($isAutoHeader === true) {
            @header('Content-Type:application/json;charset=utf-8', true);
        }
        if ($isAutoObClean === true) {
            try {
                ob_clean();
            } catch (Exception $e) {
            }
        }
        $data['data'] = empty($data['data']) ? (object)array() : $data['data'];
        $data['page'] = empty($data['page']) ? (object)array() : $data['page'];
        if ($isEcho === true) {
            echo self::toJsonString($data);
            die();
        } else {
            return self::toJsonString($data);
        }
    }
    //获取签名信息
    public static function toJsonString($data, $isNotUnescapedUnicode = true)
    {
        if ($isNotUnescapedUnicode !== true) {
            $r = json_encode($data);
        } else {
            if (version_compare(PHP_VERSION, '5.4.0', '<')) {
                $r = json_encode($data);
                $r = preg_replace_callback(
                    "#\\\u([0-9a-f]{4})#i",
                    function ($matchs) {
                        return iconv('UCS-2BE', 'UTF-8', pack('H4', $matchs[1]));
                    },
                    $r
                );
            } else {
                $r = json_encode($data, JSON_UNESCAPED_UNICODE);
            }
        }
        return $r;
    }
}