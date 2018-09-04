<?php

namespace DdvPhp\DdvRestfulApi\Lib;

use DdvPhp\DdvRestfulApi\Interfaces\RequestHeaders as RequestHeadersInterfaces;

class RequestHeaders implements RequestHeadersInterfaces
{
    // 请求头
    protected $headers = array();
    // 最后一次设置的头信息
    protected $lastSetHeaders = array();
    // app请求标识
    protected $headersPrefix = 'x-ddv-';
    // 授权头
    protected $authorization = '';
    // 主机头
    protected $host = '';
    // 主机头、不带端口
    protected $hostname = '';
    // 自定义头的keys
    protected $xHeadersKeys = [];
    // 系统头的keys
    protected $sysHeadersKeys = [];

    // 获取头信息前缀
    public function getHeadersPrefix()
    {
        return $this->headersPrefix;
    }

    public function __construct($headers = array())
    {
        $this->setHeaders($headers);
    }

    /**
     * 设置头信息前缀
     * @param null $headersPrefix
     * @return $this
     */
    public function setHeadersPrefix($headersPrefix = null)
    {
        $this->headersPrefix = is_null($headersPrefix) ? $this->headersPrefix : $headersPrefix;
        // 需要重新解析
        $this->parseHeaders();
        return $this;
    }

    // 获取头信息[授权的]
    public function getAuthorization()
    {
        return $this->authorization;
    }

    // 获取头信息[Host的]
    public function getHost()
    {
        return $this->host;
    }

    // 获取头信息[hostname，不带端口]
    public function getHostName()
    {
        return $this->hostname;
    }

    public function getHeader($key)
    {
        $keyt = $this->getHeaderKey($key, $this->headers);
        if (!empty($keyt)) {
            return $this->headers[$keyt];
        }
        return null;
    }

    public function getHeaders()
    {
        return $this->headers;
    }

    public function getXHeadersKeys()
    {
        return $this->xHeadersKeys;
    }

    public function getSysHeadersKeys()
    {
        return $this->sysHeadersKeys;
    }

    // 获取头信息[自定义]
    public function getXHeaders()
    {
        $r = [];
        foreach ($this->xHeadersKeys as $key) {
            if (isset($this->headers[$key])) {
                $r[$key] = $this->headers[$key];
            }
        }
        return $r;

    }

    // 获取头信息[系统]
    public function getSysHeaders()
    {
        $r = [];
        foreach ($this->sysHeadersKeys as $key) {
            if (isset($this->headers[$key])) {
                $r[$key] = $this->headers[$key];
            }
        }
        return $r;
    }

    /**
     * 重置头
     * @param $headers
     * @return $this
     */
    public function setHeaders($headers)
    {
        if (!is_array($headers)) {
            $headers = array();
        }
        $this->lastSetHeaders = $headers;
        $this->headers = $this->parseHeaders();
        return $this;

    }

    protected function parseHeaders($headers = null)
    {
        if (empty($headers)) {
            $headers = $this->lastSetHeaders;
        }
        if (!is_array($headers)) {
            $headers = array();
        }

        // 前缀
        $headersPrefix = str_replace('-', '_', strtolower($this->getHeadersPrefix()));
        //所有headers参数传输的前缀
        $headersPrefixLen = strlen($headersPrefix);
        // http_长度
        $httpPrefixlen = strlen('http_');
        // 主机头
        $host = $this->parseHost($headers);
        // 授权头
        $authorization = $this->parseAuthorization($headers);
        // 授权头
        if (!empty($authorization)) {
            $this->authorization = $authorization;
        }
        // 主机头
        if (!empty($host)) {
            $this->host = $host;
            //试图去除端口
            try {
                $parseUrlTemp = parse_url($host);
                $hostname = isset($parseUrlTemp['host']) ? $parseUrlTemp['host'] : $host;
                unset($parseUrlTemp);
            } catch (Exception $e) {
                $hostname = $host;
            }
            $this->hostname = $hostname;
            unset($hostname);
        }

        $xHeadersKeys = [];
        $sysHeadersKeys = [];

        // 主机头保存
        $hostKey = $this->getHeaderKey('host', $headers, 'Host');
        if ($host) {
            $headers[$hostKey] = $host;
            $sysHeadersKeys[] = $hostKey;
        }

        // Content-Type
        $ContentTypeKey = $this->getHeaderKey('content-type', $headers, 'Content-Type');
        $ContentType = $this->parseContentType($headers);
        if (!empty($ContentType)) {
            $headers[$ContentTypeKey] = $ContentType;
            $sysHeadersKeys[] = $ContentTypeKey;
        }

        // Content-Md5
        $ContentMd5Key = $this->getHeaderKey('content-md5', $headers, 'Content-Md5');
        $ContentMd5 = $this->parseTryByKey('content-md5', $headers);
        if (!empty($ContentMd5)) {
            $headers[$ContentMd5Key] = $ContentMd5;
            $sysHeadersKeys[] = $ContentMd5Key;
        }

        // Content-Md5
        $ContentLengthKey = $this->getHeaderKey('content-length', $headers, 'Content-Length');
        $ContentLength = $this->parseTryByKey('content-length', $headers);
        if (!empty($ContentLength)) {
            $headers[$ContentLengthKey] = $ContentLength;
            $sysHeadersKeys[] = $ContentLengthKey;
        }


        foreach ($headers as $keyt => $value) {
            $keytLower = strtolower($keyt);
            if (substr($keytLower, 0, $headersPrefixLen) == $headersPrefix) {
                $headers[$keyt] = $value;
                $xHeadersKeys[] = $keyt;
            }
            unset($keytLower);
        }
        foreach ($_SERVER as $keyot => $value) {
            $keyt = substr($keyot, $httpPrefixlen);
            $keytLower = strtolower($keyt);
            if (substr($keytLower, 0, $headersPrefixLen) == $headersPrefix) {
                if (!$this->getHeaderKey($keyt, $headers)) {
                    $headers[$keyt] = $value;
                    $xHeadersKeys[] = $keyt;
                }
            }
            unset($keyt, $keytLower);
        }
        $this->xHeadersKeys = $xHeadersKeys;
        $this->sysHeadersKeys = $sysHeadersKeys;
        unset($headersPrefix, $headersPrefixLen, $httpPrefixlen, $host, $authorization, $xHeadersKeys, $sysHeadersKeys, $hostKey, $ContentTypeKey, $ContentType, $ContentMd5Key, $ContentMd5, $ContentLengthKey, $ContentLength);
        //返回
        return $headers;
    }

    protected function parseContentType($headers = array())
    {
        // 主机头
        $contentType = '';
        // 试图直接请求头读取
        if (isset($headers['content-type'])) {
            $contentType = $headers['content-type'];
        }
        // 试图通过  获取
        if (empty($contentType)) {
            $contentType = $this->parseTryByKey('content-type', $headers);
        }
        if (isset($contentType) && strpos($contentType, 'multipart/restful-form-data') !== false) {
            if (isset($_SERVER['REDIRECT_RESTFUL_MULTIPART_TYPE'])) {
                $contentType = $_SERVER['REDIRECT_RESTFUL_MULTIPART_TYPE'];
            }
        }
        if (isset($contentType) && strpos($contentType, 'multipart/restful-form-data') !== false) {
            if (isset($_SERVER['REDIRECT_HTTP_CONTENT_TYPE'])) {
                $contentType = $_SERVER['REDIRECT_HTTP_CONTENT_TYPE'];
            }
        }
        return $contentType;
    }

    protected function parseHost($headers = array())
    {
        // 主机头
        $host = '';
        // 试图直接请求头读取
        if (isset($headers['host'])) {
            $host = $headers['host'];
        }
        // 试图通过  获取
        if (empty($host)) {
            $host = $this->parseTryByKey('host', $headers);
        }
        return $host;
    }

    protected function parseAuthorization($headers = array())
    {
        // 授权头
        $authorization = '';
        $urlPrefix = $this->getHeadersPrefix();
        if (empty($authorization) && !empty($_GET[$urlPrefix . 'authorization'])) {
            $authorization = $_GET[$urlPrefix . 'authorization'];
            unset($_GET[$urlPrefix . 'authorization']);
        }
        if (empty($authorization) && !empty($_GET[$urlPrefix . 'auth'])) {
            $authorization = $_GET[$urlPrefix . 'auth'];
            unset($_GET[$urlPrefix . 'auth']);
        }
        // 试图直接请求头读取
        if (empty($authorization) && isset($headers['authorization'])) {
            $authorization = $headers['authorization'];
        }
        // 试图通过 HTTP_AUTHORIZATION 获取
        if (empty($authorization)) {
            $authorization = $this->parseTryByKey('authorization', $headers);
        }
        return $authorization;
    }

    public function getHeaderKey($key, $headers = null, $defaultValue = null)
    {
        if (!is_array($headers)) {
            $headers = $this->headers;
        }
        $keyLower = str_replace('-', '_', strtolower($key));
        // 试图通过 HTTP_AUTHORIZATION 获取
        foreach ($headers as $keyt => $value) {
            $keytLower = str_replace('-', '_', strtolower($keyt));
            if ($keytLower === $keyLower || $keytLower === ('http_' . $keyLower)) {
                return empty($keyt) ? $defaultValue : $keyt;
            }
        }
        return $defaultValue;
    }

    protected function parseTryByKey($key, $headers = array())
    {
        // 主机头
        $res = '';
        $keyLower = str_replace('-', '_', strtolower($key));
        $keyUpper = str_replace('-', '_', strtoupper($key));
        // 试图直接请求头读取
        if (empty($res) && isset($headers[$key])) {
            $res = $headers[$key];
        }
        // 试图通过 大写下滑杆 获取
        if (empty($res)) {
            foreach ($headers as $keyt => $value) {
                $keytLower = str_replace('-', '_', strtolower($keyt));
                if ($keytLower === $keyLower || $keytLower === ('http_' . $keyLower)) {
                    $res = $value;
                }
            }
        }
        // 试图 apache_request_headers 获取
        if (empty($res)) {
            if (function_exists('apache_request_headers')) {
                foreach (apache_request_headers() as $keyt => $value) {
                    $keytLower = str_replace('-', '_', strtolower($keyt));
                    if ($keytLower === $keyLower && !empty($value)) {
                        $res = $value;
                    }
                }
            }
        }
        // 试图 $_SERVER['HTTP_AUTHORIZATION'] 获取
        if (empty($res) && isset($_SERVER) && isset($_SERVER[('HTTP_' . $keyUpper)])) {
            $res = $_SERVER[('HTTP_' . $keyUpper)];
        }
        return $res;
    }


}
