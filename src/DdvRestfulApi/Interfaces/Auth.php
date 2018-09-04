<?php
/**
 * Created by PhpStorm.
 * User: hua
 * Date: 2018/9/3
 * Time: 上午11:33
 */

namespace DdvPhp\DdvRestfulApi\Interfaces;

interface Auth
{
    public function sign();

    public function setConfig($config = array());

    /**
     * @param RequestInfo $requestInfo
     * @return $this|RestfulApi
     */
    public function setRequestSignInfo(RequestSignInfo $signInfo);

    /**
     * 获取授权数据
     * @param $sessionId
     * @return array
     */
    public function getAuthData($sessionId);

    /**
     * 设置授权数据的存储
     * @param $sessionId
     * @param null $data
     * @return null
     */
    public function saveAuthData($sessionId, $data = null);

    /**
     * 获取授权版本
     * @return null|string
     */
    public function getVersion();

    /**
     * 获取授权签名信息
     * @return string
     */
    public function getAuthorization();

    /**
     * 获取授权数据驱动类
     * @return string
     */
    public function getAuthDataDriver();

    /**
     * 获取授权数据驱动配置数据
     * @return array
     */
    public function getAuthDataDriverConfig();

    /**
     * @return RequestSignInfo
     */
    public function getRequestSignInfo();

    /**
     * 获取授权签名
     * @return authSign
     */
    public function getAuthSign();

    /**
     * 获取授权id
     * @return string
     */
    public function getAccessKeyId();

    /**
     * @param null $sessionId
     * @param string $path
     * @param array $query
     * @param array $noSignQuery
     * @param string $method
     * @param array $headers
     * @param null $authClassName
     * @return string
     */
    public function getSignUrl($sessionId = null, $path = '/', $query = array(), $noSignQuery = array(), $method = 'GET', $headers = array(), $authClassName = null);

    /**
     * @param null $sessionId
     * @param string $url
     * @param array $noSignQuery
     * @param string $method
     * @param array $query
     * @param array $headers
     * @param null $authClassName
     * @return string
     */
    public function getSignUrlByUrl($sessionId = null, $url = '/', $noSignQuery = array(), $method = 'GET', $query = array(), $headers = array(), $authClassName = null);
}
