<?php
/**
 * Created by PhpStorm.
 * User: sicmouse
 * Date: 2018/9/4
 * Time: 下午1:32
 */

namespace DdvPhp\DdvRestfulApi\Interfaces;



interface RestfulApiEcho extends RestfulApi
{

    public function isDevelopment();
    /**
     * [onHandler 监听错误]
     * @author: 桦 <yuchonghua@163.com>
     * @DateTime 2017-04-26T18:55:58+0800
     * @return   [type]                   [description]
     */
    public function onHandler($r, $e);

    /**
     * [echoData 输出]
     * @author: 桦 <yuchonghua@163.com>
     * @DateTime 2017-04-26T18:56:12+0800
     */
    public function echoData($data);

    /**
     * [echo404 输出]
     * @author: 桦 <yuchonghua@163.com>
     * @DateTime 2017-04-26T18:56:12+0800
     */
    public function echo404($message = 'Api interface not found', $errorId = '404 Not Found', $statusCode = 404);

    /**
     * [setHandler 输出]
     * @author: 桦 <yuchonghua@163.com>
     * @DateTime 2017-04-26T18:56:12+0800
     */
    public function echoStr($data, $isEcho = true, $isAutoHeader = true, $isAutoSessionClose = true, $isAutoObClean = null, $isNotUnescapedUnicode = true);

    /**
     * @param null $parameters
     * @param string $cookies
     * @param null $files
     * @param null $server
     * @param array $headers
     * @return $this|RestfulApi
     */
    public function requestParse($parameters = null, $cookies = '', $files = null, $server = null, $headers = array());
    /**
     * [setHandler 设置错误监听]
     * @author: 桦 <yuchonghua@163.com>
     * @DateTime 2017-04-26T18:56:12+0800
     * @return $this|RestfulApi
     */
    public function useHandler();

    /**
     * @return mixed
     */
    public function authSign();
}