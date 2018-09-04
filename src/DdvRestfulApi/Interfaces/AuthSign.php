<?php
/**
 * Created by PhpStorm.
 * User: sicmouse
 * Date: 2018/9/3
 * Time: 上午11:36
 */

namespace DdvPhp\DdvRestfulApi\Interfaces;

interface AuthSign
{
    /**
     * @return null
     */
    public function sign();

    /**
     * 获取授权数据
     * @param $accessKeyId
     * @return array
     */
    public function getAuthData($accessKeyId);

    /**
     * 设置授权数据的存储
     * @param $accessKeyId
     * @param null $data
     * @return null
     */
    public function saveAuthData($accessKeyId, $data = null);

    /**
     * 获取签名后的url
     * @param null $accessKeyId
     * @param string $path
     * @param array $query
     * @param array $noSignQuery
     * @param string $method
     * @param array $headers
     * @param null $authClassName
     * @return string
     */
    public function getSignUrl($accessKeyId = null, $path = '/', $query = array(), $noSignQuery = array(), $method = 'GET', $headers = array(), $authClassName = null);

    /**
     * 对url进行签名
     * @param null $accessKeyId
     * @param string $url
     * @param array $noSignQuery
     * @param string $method
     * @param array $query
     * @param array $headers
     * @param null $authClassName
     * @return string
     */
    public function getSignUrlByUrl($accessKeyId = null, $url = '/', $noSignQuery = array(), $method = 'GET', $query = array(), $headers = array(), $authClassName = null);

    /**
     * 判断session_card
     * @param string $card_id
     * @return mixed
     */
    public function isSessionCard($card_id = '');

    /**
     * 生成session_card
     * @return string
     */
    public function createSessionCard();

    /**
     * 生成session_key
     * @param null $session_card
     * @return string
     */
    public function createSessionKey($session_card = null);

    /**
     * @return mixed
     */
    public function generateSessionId();
}
