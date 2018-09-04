<?php

namespace DdvPhp\DdvRestfulApi\Interfaces;

interface RequestHeaders
{
    // 获取头信息前缀
    public function getHeadersPrefix();

    // 设置头信息前缀
    public function setHeadersPrefix($headersPrefix = null);

    // 获取头信息[授权的]
    public function getAuthorization();

    // 获取头信息[Host的]
    public function getHost();

    // 获取头信息[hostname，不带端口]
    public function getHostName();

    // 获取头信息[指定]
    public function getHeader($key);

    // 获取头信息[所有]
    public function getHeaders();

    // 获取头信息[自定义]
    public function getXHeaders();

    // 获取头信息[系统]
    public function getSysHeaders();

    // 获取头信息[自定义]
    public function getXHeadersKeys();

    // 获取头信息[系统]
    public function getSysHeadersKeys();

    // 更加key获取对应真实的key
    public function getHeaderKey($key, $headers = null, $defaultValue = null);

    // 重置头
    public function setHeaders($headers);
}
