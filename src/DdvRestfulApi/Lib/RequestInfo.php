<?php
/**
 * Created by PhpStorm.
 * User: sicmouse
 * Date: 2018/8/30
 * Time: 下午1:34
 */

namespace DdvPhp\DdvRestfulApi\Lib;

use \DdvPhp\DdvRestfulApi\Exception;
use \DdvPhp\DdvRestfulApi\Interfaces\RequestInfo as RequestInfoInterface;
use \DdvPhp\DdvRestfulApi\Interfaces\RequestHeaders as RequestHeadersInterface;

class RequestInfo extends RequestHeaders implements RequestHeadersInterface, RequestInfoInterface
{
    public static $headRequestMethods = array('GET', 'HEAD', 'OPTIONS');
    protected $method = '';
    protected $server = array();
    protected $files = array();
    protected $parameters = array();
    protected $query = array();
    protected $cookies = array();
    protected $contentHashRes = array();
    protected static $initKeys = array('server', 'files', 'data');

    public function __construct($parameters = null, $cookies = '', $files = null, $server = null, $headers = array())
    {
        $this->requestInfoInit($parameters, $cookies, $files, $server, $headers);
    }

    public function requestInfoInit($parameters = null, $cookies = '', $files = null, $server = null, $headers = array())
    {
        if (!is_string($cookies)) {
            throw new Exception('$cookies must is string');
        }
        if (is_null($server)) {
            $this->server = $_SERVER;
        } else {
            $this->server = $server;
        }
        if (is_null($files)) {
            $this->files = $_FILES;
        } else {
            $this->files = $files;
        }
        if (is_null($files)) {
            $this->files = $_FILES;
        } else {
            $this->files = $files;
        }
        if (is_null($parameters)) {
            $this->parameters = $_POST;
        } else {
            $this->parameters = $parameters;
        }
        if (empty($headers) || !is_array($headers)) {
            $headers = array();
        }

        // 解析头
        $this->setHeaders($headers);
        // 解析Method
        $this->parseMethod();
        // 解析Query
        $this->parseQuery();
        // 解析Cookie
        $this->parseCookies($cookies);
    }

    public function getContentHash($key, $type = 'hex')
    {
        if (empty($this->contentHashRes[$key])) {
            return null;
        }
        if ($type === 'raw') {
            return $this->contentHashRes[$key];
        } elseif ($type === 'base64') {
            return base64_encode($this->contentHashRes[$key]);
        } else {
            return bin2hex($this->contentHashRes[$key]);
        }
    }

    public function setContentHash($data = array())
    {
        $this->contentHashRes = $data;
        return $this;
    }

    // 获取 输入的ContentMd5
    public function getInputContentMd5Hex()
    {
        //生成hex_md5
        return $this->getContentHash('md5', 'hex');
    }

    public function getInputContentMd5Base64()
    {
        //生成hex_md5
        return $this->getContentHash('md5', 'base64');
    }

    public function getHeaderContentMd5()
    {
        $contentMd5 = $this->getHeader('content-md5');
        return empty($contentMd5) ? '' : $contentMd5;
    }

    public function getUri()
    {
        return empty($this->server['REQUEST_URI']) ? '/' : $this->server['REQUEST_URI'];
    }

    public function getMethod()
    {
        if (empty($this->method)) {
            $this->method = strtoupper(empty($_SERVER['REQUEST_METHOD']) ? 'GET' : $_SERVER['REQUEST_METHOD']);
        }
        return $this->method;
    }

    public function getQuerys()
    {
        return $this->query;
    }

    public function getParameters()
    {
        return $this->parameters;
    }

    public function getFiles()
    {
        return $this->files;
    }

    public function getServers()
    {
        return $this->server;
    }

    public function getCookies()
    {
        return $this->cookies;
    }

    public function isHeadRequest()
    {
        return in_array($this->getMethod(), self::$headRequestMethods);
    }

    protected function parseMethod()
    {
        $this->method = isset($this->server['REQUEST_METHOD']) ? $this->server['REQUEST_METHOD'] : '';
    }

    protected function parseQuery()
    {
        if (empty($this->server['QUERY_STRING'])) {
            $this->server['QUERY_STRING'] = parse_url($this->getUri(), PHP_URL_QUERY);
        }
        if (empty($this->server['QUERY_STRING'])) {
            $this->server['QUERY_STRING'] = '';
        } else {
            // $GET
            parse_str($this->server['QUERY_STRING'], $this->query);
        }
    }

    protected function parseCookies()
    {
        $cookie = $this->getHeader('cookie');
        if (empty($cookie) && isset($this->server['HTTP_COOKIE'])) {
            $cookie = $this->server['HTTP_COOKIE'];
        }
        if (!empty($cookie)) {
            $this->server['HTTP_COOKIE'] = $cookie;
            parse_str(str_replace('; ', '&', $cookie), $this->cookies);
        }
        if (!is_array($this->cookies)) {
            $this->cookies = array();
        }
        unset($cookie);
    }
}
