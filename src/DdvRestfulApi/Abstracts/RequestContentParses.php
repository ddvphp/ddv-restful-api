<?php
/**
 * Created by PhpStorm.
 * User: sicmouse
 * Date: 2018/8/31
 * Time: 下午2:44
 */

namespace DdvPhp\DdvRestfulApi\Abstracts;

use Closure;
use DdvPhp\DdvRestfulApi\Interfaces\HttpRequestStream;
use DdvPhp\DdvRestfulApi\Interfaces\RequestContentParses as RequestContentParsesInterfaces;
use DdvPhp\DdvRestfulApi\Lib\HttpCache;

abstract class RequestContentParses implements RequestContentParsesInterfaces
{
    /**
     * 写入长度
     * @var int
     */
    protected $writeLength = 0;
    // 计算出内容长度
    protected $contentLength = null;
    /**
     * 毁掉钩子
     * @var array
     */
    protected $hookCompleteds = [];
    /**
     * @var HttpRequestStream|null
     */
    protected $httpRequestStream = null;
    /**
     * @var bool
     */
    protected $isCompleted = false;
    /**
     * 回车 CRLF
     * @var string|null
     */
    protected $CRLF = null;
    /**
     * 写入临时缓冲区
     * @var string
     */
    protected $writeTempBuffer = '';

    /**
     * RequestContentMultipart constructor.
     * @param HttpRequestStream $httpRequestStream
     */
    public function __construct(HttpRequestStream $httpRequestStream)
    {
        // 回车 CRLF
        $this->CRLF = HttpCache::$CRLF;
        // 请求解析流
        $this->httpRequestStream = $httpRequestStream;
    }

    public function onCompleted(Closure $fn)
    {
        if (!is_array($this->hookCompleteds)) {
            $this->hookCompleteds = [];
        }
        $this->hookCompleteds[] = $fn;
    }

    protected function checkCompleted()
    {
        if ($this->isCompleted() && is_array($this->hookCompleteds)) {
            $fns = $this->hookCompleteds;
            foreach ($fns as $fn) {
                if ($fn instanceof Closure) {
                    $fn();
                }
            }
            unset($fns);
        }
    }

    public function isCompleted()
    {
        if ($this->isCompleted !== true && $this->writeLength >= $this->contentLength) {
            $this->isCompleted = true;
        }
        return $this->isCompleted;
    }

    /**
     * 重置
     */
    public function reset()
    {
        // 写入长度为0
        $this->writeLength = 0;
        // 计算出内容
        $this->contentLength = $this->httpRequestStream->getContentLength();
        // 钩子清空
        $this->hookCompleteds = [];
        // 标记为未完成
        $this->isCompleted = false;
        // 写入临时缓冲区
        $this->writeTempBuffer = '';
    }

    public function destroy()
    {
        $this->reset();
        $this->hookCompleteds = null;
    }
}
