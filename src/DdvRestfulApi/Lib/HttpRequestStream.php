<?php
/**
 * Created by PhpStorm.
 * User: sicmouse
 * Date: 2018/8/31
 * Time: 上午9:15
 */

namespace DdvPhp\DdvRestfulApi\Lib;

use \Closure;
use DdvPhp\DdvRestfulApi\Exception\RequestParseError;
use DdvPhp\DdvRestfulApi\Interfaces\HttpRequestStream as HttpRequestStreamInterfaces;
use DdvPhp\DdvRestfulApi\Interfaces\RequestContentParses;

class HttpRequestStream implements HttpRequestStreamInterfaces
{

    /**
     * Maximum acceptable packet size.
     *
     * @var int
     */
    public static $maxPackageSize = 10485760;
    /**
     * The supported HTTP methods
     * @var array
     */
    public static $methods = array('GET', 'POST', 'PUT', 'DELETE', 'HEAD', 'OPTIONS');
    public static $contentTypeParses = array(
        'multipart/form-data' => RequestContentMultipart::class,
        'text/x-www-form-urlencoded' => RequestContentUrlencoded::class,
        'application/x-www-form-urlencoded' => RequestContentUrlencoded::class,
    );
    /**
     * @var RequestContentParses|null
     */
    protected $contentTypeParse = null;
    /**
     * 写入临时缓冲区
     * @var string
     */
    protected $writeTempBuffer = '';
    /**
     * 头缓冲区
     * @var string
     */
    protected $headerBuffer = '';
    /**
     * 计算出头的长度
     * @var int|null
     */
    protected $headerLength = null;
    /**
     * 计算出内容长度
     * @var int|null
     */
    protected $contentLength = null;
    /**
     * 请求内容类型
     * @var null
     */
    protected $contentType = '';
    /**
     * 需要hash的列表
     * @var array
     */
    protected $contentHashKeys = array('md5');
    /**
     * 增量hash上下文
     * @var array
     */
    protected $contentHashCtxs = array();
    /**
     * 需要hash的结果
     * @var array
     */
    protected $contentHashRes = array();
    /**
     * 头初始化完毕
     * @var bool
     */
    protected $headerInited = false;
    /**
     * 内容初始化完毕
     * @var bool
     */
    protected $contentInited = false;
    /**
     * 分隔符
     * @var string
     */
    protected $boundary = '';
    /**
     * 原始头
     * @var array
     */
    protected $rawHeaders = array();
    /**
     * 头
     * @var array
     */
    protected $headers = array();
    /**
     * data
     * @var array
     */
    protected $parameters = array();
    /**
     * 上传文件集合
     * @var array
     */
    protected $files = array();
    /**
     * @var array
     */
    protected $server = array();
    /**
     * 回车 CRLF
     * @var string|null
     */
    protected $CRLF = null;
    /**
     * 保存写入缓冲区的地址
     * 如果为false将不保存
     * 如果为null将保存临时目录
     * 如果为true就保存到$writeBuffer
     * 字符串就保存到具体文件
     * @var bool
     */
    protected $writeBufferSavePath = false;

    /**
     * 解析完成时候的回调，这个会随重置方法调用而清空
     * @var array
     */
    protected $hookCompleteds = array();
    /**
     * 这个是请求监听，这个不会随重置方法调用而清空
     * @var array
     */
    protected $hookRequesteds = array();

    /**
     * HttpRequestStream constructor.
     */
    public function __construct()
    {
        // 回车 CRLF
        $this->CRLF = HttpCache::$CRLF;
        // 重置数据
        $this->reset();
    }

    public function reset()
    {
        // 清空回调钩子
        $this->hookCompleteds = array();
        // 写入临时缓冲区
        $this->writeTempBuffer = '';
        // 计算出头的长度
        $this->headerLength = null;
        // 计算出内容长度
        $this->contentLength = null;
        // 头初始化完毕
        $this->headerInited = false;
        // 内容初始化完毕
        $this->contentInited = false;
        // 分隔符
        $this->boundary = '';
        // 重置原始头
        $this->rawHeaders = array();
        // 重置头
        $this->headers = array();
        // 重置data
        $this->parameters = array();
        // 重置文件
        $this->files = array();
        // 服务解析
        $this->server = array(
            'QUERY_STRING' => '',
            'REQUEST_METHOD' => '',
            'REQUEST_URI' => '',
            'SERVER_PROTOCOL' => '',
            'SERVER_SOFTWARE' => 'ddvRestfulApi/' . '1.0',
            'SERVER_NAME' => '',
            'HTTP_HOST' => '',
            'HTTP_USER_AGENT' => '',
            'HTTP_ACCEPT' => '',
            'HTTP_ACCEPT_LANGUAGE' => '',
            'HTTP_ACCEPT_ENCODING' => '',
            'HTTP_COOKIE' => '',
            'HTTP_CONNECTION' => '',
            'CONTENT_TYPE' => '',
            'REMOTE_ADDR' => '',
            'REMOTE_PORT' => '0',
            'REQUEST_TIME' => time()
        );
        // 如果有，需要销毁
        if ($this->contentTypeParse instanceof RequestContentParses) {
            $this->contentTypeParse->destroy();
            $this->contentTypeParse = null;
        }
        // 返回链式调用
        return $this;
    }

    public function write($buffer)
    {
        if ($this->isHeaderInited()) {
            $this->parseContentChunk($buffer);
        } else {
            $this->parseHttpHeader($buffer);
        }
    }

    protected function parseContentChunk($buffer)
    {
        if (empty($this->contentTypeParse) || !($this->contentTypeParse instanceof RequestContentParses)) {
            // 解析写入临时缓冲区的内容
            $this->contentTypeParseInit();
        }
        if (isset($this->contentTypeParse) && $this->contentTypeParse instanceof RequestContentParses) {
            // 增量
            foreach ($this->contentHashKeys as $key) {
                // 如果有 hash
                if (!empty($this->contentHashCtxs[$key])) {
                    // 增量哈希
                    hash_update($this->contentHashCtxs[$key], $buffer);
                }
            }
            $this->contentTypeParse->write($buffer);
        }
    }

    protected function contentTypeParseInit()
    {
        $this->onCompleted(function () {
            // 生成请求头信息 - 触发请求
            $requestInfo = new RequestInfo($this->parameters, '', $this->files, $this->server, $this->getHeaders());
            $requestInfo->setContentHash($this->contentHashRes);
            $this->emitRequested($requestInfo);
            // 触发重置，注意，完成触发后会马上重置，迎接下一次请求
            $this->reset();
        });
        if ((empty($this->server['CONTENT_TYPE']) || empty($this->contentLength)) && empty($buffer)) {
            // 标记完成
            $this->contentInited = true;
            // 触发完成
            $this->emitCompleted();
            return;
        }
        // 增量
        foreach ($this->contentHashKeys as $key) {
            // 如果有 hash
            if (!empty($this->contentHashCtxs[$key])) {
                // 结束
                hash_final($this->contentHashCtxs[$key]);
            }
            // 初始化增量 hash 运算上下文
            $this->contentHashCtxs[$key] = hash_init($key);
            // 清空结果
            $this->contentHashRes[$key] = '';
        }
        $type = explode(';', $this->server['CONTENT_TYPE'], 2)[0];
        $className = static::$contentTypeParses[$type];
        $obj = null;
        //可以有更多的匹配方式
        if (class_exists($className)) {
            // 实例化
            $obj = new $className($this);
        }
        if ($obj instanceof RequestContentParses) {
            // 实例化
            $this->contentTypeParse = $obj;
            // 完成的时候
            $this->contentTypeParse->onCompleted(function () {
                // 增量
                foreach ($this->contentHashKeys as $key) {
                    // 如果有 hash
                    if ($this->contentHashCtxs[$key] !== null) {
                        $res = array();
                        // 增量哈希
                        $this->contentHashRes[$key] = hash_final($this->contentHashCtxs[$key], true);
                        $this->contentHashCtxs[$key] = null;
                    }
                }
                // 标记完成
                $this->contentInited = true;
                // 触发完成
                $this->emitCompleted();
            });
        } else {
            throw new RequestParseError('解析数据失败', 'Bad Reques', 400);
        }
        unset($obj, $className);
    }

    protected function parseHeader($bufferOrArray = array())
    {
        $rawArrays = array();
        $headers = array();
        if (is_string($bufferOrArray)) {
            $rawArrays = explode($this->CRLF, $bufferOrArray);
        } elseif (is_array($bufferOrArray)) {
            $rawArrays = $bufferOrArray;
        }
        foreach ($rawArrays as $content) {
            // \r\n\r\n 切割换号有可能会有空的内容
            if (empty($content)) {
                continue;
            }
            list($key, $value) = explode(':', $content, 2);
            $key = trim($key);
            $value = trim($value);
            $headers[$key] = $value;
        }

        return $headers;
    }

    protected function parseHttpHeader($buffer)
    {
        // 写入临时缓冲区
        $this->writeTempBuffer .= $buffer;
        if (strpos($this->writeTempBuffer, $this->CRLF . $this->CRLF)) {
            // 判断包装长度是否超过限制。
            if (strlen($this->writeTempBuffer) >= static::$maxPackageSize) {
                // 太大的头缓冲区
                throw new RequestParseError('Header Buffer too large', 'Bad Reques', 400);
            }
            list($headerBuffer, $buffer) = explode($this->CRLF . $this->CRLF, $this->writeTempBuffer, 2);

            $server = array();
            $this->baseInit($headerBuffer, $server);
            // 解析内容
            $this->parseContentChunk($buffer);
            unset($uri, $method, $protocol, $rawHeaders, $headerBuffer, $key, $value);
        }
        unset($buffer);
    }
    public function baseInit ($headers = array(), $server = array()) {
        if(is_string($headers)){
            if (empty($server)||!is_array($server)){
                $server = array();
            }
            // 保存头Buffer
            $this->headerBuffer = $headers;
            // 计算长度
            $this->headerLength = strlen($this->headerBuffer);
            // 获取原始头
            $this->rawHeaders = explode($this->CRLF, $this->headerBuffer);
            // 获取[请求方式,uri,协议]
            list($server['REQUEST_METHOD'], $server['REQUEST_URI'], $server['SERVER_PROTOCOL']) = explode(' ', $this->rawHeaders[0]);
            // 判断请求方式是在允许接收的里面
            if (static::$methods !== false && !in_array($server['REQUEST_METHOD'], static::$methods)) {
                throw new RequestParseError('Bad Reques', 'Bad Reques', 400);
            }
            // 解析头
            $this->headers = $this->parseHeader(array_slice($this->rawHeaders, 1));
            foreach ($this->headers as $key => $value) {
                $key = str_replace('-', '_', strtoupper($key));
                $server['HTTP_' . $key] = $value;
                switch ($key) {
                    // HTTP_HOST
                    case 'HOST':
                        $tmp = explode(':', $value);
                        $server['SERVER_NAME'] = $tmp[0];
                        if (isset($tmp[1])) {
                            $server['SERVER_PORT'] = $tmp[1];
                        }
                        unset($tmp);
                        break;
                    // content-type
                    case 'CONTENT_TYPE':
                        if (!preg_match('/boundary="?(\S+)"?/', $value, $match)) {
                            if ($pos = strpos($value, ';')) {
                                $server['CONTENT_TYPE'] = substr($value, 0, $pos);
                            } else {
                                $server['CONTENT_TYPE'] = $value;
                            }
                            unset($pos);
                        } else {
                            $server['CONTENT_TYPE'] = 'multipart/form-data';
                            $this->boundary = '--' . $match[1];
                        }
                        break;
                    case 'CONTENT_LENGTH':
                        $server['CONTENT_LENGTH'] = $value;
                        break;
                }
            }
        }else{
            if(empty($server)){
                throw new RequestParseError('server params error', 'SERVER_PARAMS_ERROR', 500);
            }
            // 计算长度
            $this->headerLength = 0;
        }
        if (empty($headers)||!is_array($headers)){
            $headers = array();
            foreach ($server as $name => $value)
            {
                if (substr($name, 0, 5) == 'HTTP_')
                {
                    $headers[str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($name, 5)))))] = $value;
                }
            }
        }
        if (empty($this->contentLength)&&isset($server['CONTENT_LENGTH'])){
            $this->contentLength = $server['CONTENT_LENGTH'];
        }
        if (empty($this->contentLength)){
            foreach ($headers as $name => $value){
                if (strtolower($name)==='content-length'){
                    $this->contentLength = $value;
                }
            }
        }
        // 存储头
        $this->headers = $headers;
        // 存储server
        $this->server = array_merge($this->server, $server);
        // 头初始化完毕
        $this->headerInited = true;
        // 清空临时缓冲区
        $this->writeTempBuffer = '';
        return $this;
    }

    public function getContentLength()
    {
        // 检测头是否初始化完成
        $this->checkHeaderInited();
        if ($this->contentLength) {
            return (int)$this->contentLength;
        } else {
            return 0;
        }
    }

    public function isHeaderInited()
    {
        return $this->headerInited;
    }

    public function checkHeaderInited()
    {
        if (!$this->isHeaderInited()) {
            throw new RequestParseError('Header is not initialized', 'HEADER_IS_NOT_INITIALIZED', 400);
        }
    }

    public function getMultipartBoundary()
    {
        return $this->boundary;
    }

    public function setDatas($parameters)
    {
        $this->parameters = $parameters;
        return $this;
    }

    public function setFiles($files)
    {
        $this->files = $files;
        return $this;
    }

    public function getHeaders()
    {
        return $this->headers;
    }

    /**
     * 触发重置，注意，完成触发后会马上重置，迎接下一次请求
     * @param Closure $fn
     */
    public function onCompleted(Closure $fn)
    {
        if (isset($this->hookCompleteds) && !is_array($this->hookCompleteds)) {
            $this->hookCompleteds = [];
        }
        $this->hookCompleteds[] = $fn;
    }

    /**
     * 触发重置，注意，完成触发后会马上重置，迎接下一次请求
     * @param Closure $fn
     */
    public function onRequested(Closure $fn)
    {
        if (isset($this->hookRequesteds) && !is_array($this->hookRequesteds)) {
            $this->hookRequesteds = [];
        }
        $this->hookRequesteds[] = $fn;
    }

    protected function emitRequested(RequestInfo $requesteInfo)
    {
        if (is_array($this->hookRequesteds)) {
            $fns = $this->hookRequesteds;
            foreach ($fns as $fn) {
                if ($fn instanceof Closure) {
                    call_user_func($fn, $requesteInfo);
                }
            }
            unset($fns);
        }
        return $this;
    }

    protected function emitCompleted()
    {
        if (is_array($this->hookCompleteds)) {
            $fns = $this->hookCompleteds;
            foreach ($fns as $fn) {
                if ($fn instanceof Closure) {
                    $fn();
                }
            }
            unset($fns);
        }
        return $this;
    }

    public function setRemoteInfo($remoteAddress, $remotePort, $info = array())
    {
        $this->server['REMOTE_ADDR'] = $remoteAddress;
        $this->server['REMOTE_PORT'] = $remotePort;
        return $this;
    }
    public function destroy()
    {
        $this->reset();
        $this->hookCompleteds = null;
    }
}
