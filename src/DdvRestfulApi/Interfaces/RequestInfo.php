<?php

namespace DdvPhp\DdvRestfulApi\Interfaces;

interface RequestInfo extends RequestHeaders
{
    public function getContentHash($key, $type = 'hex');

    public function setContentHash($data = array());

    public function getHeaderContentMd5();

    public function getInputContentMd5Hex();

    public function getInputContentMd5Base64();

    /**
     * 请求方式
     * @return string
     */
    public function getUri();

    public function getMethod();

    public function isHeadRequest();

    public function getQuerys();

    public function getParameters();

    public function getFiles();

    public function getServers();

    public function getCookies();

    public function requestInfoInit($parameters = null, $cookies = '', $files = null, $server = null, $headers = array());
}
