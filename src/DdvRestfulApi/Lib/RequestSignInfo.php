<?php
/**
 * Created by PhpStorm.
 * User: sicmouse
 * Date: 2018/8/30
 * Time: 下午1:34
 */

namespace DdvPhp\DdvRestfulApi\Lib;

use DdvPhp\DdvRestfulApi\Abstracts\RequestSignInfo as RequestSignInfoAbstracts;
use \DdvPhp\DdvRestfulApi\Interfaces\RequestInfo as RequestInfoInterface;
use \DdvPhp\DdvRestfulApi\Interfaces\RequestSignInfo as RequestSignInfoInterface;
use \DdvPhp\DdvRestfulApi\Interfaces\RequestHeaders as RequestHeadersInterface;


class RequestSignInfo extends RequestSignInfoAbstracts implements RequestHeadersInterface, RequestInfoInterface, RequestSignInfoInterface
{
    protected $isInit = false;
    protected $isValidationContentLength = false;
    protected $isValidationContentMd5 = false;
    protected $isPassContentMd5 = false;
    protected $isPassContentLength = false;
    protected $inputContentMd5Base64 = null;
    protected $inputContentMd5Hex = null;
    protected $contentLength = null;

    public function createRequestInfo(RequestInfoInterface $requestInfo = null)
    {
        if (isset($requestInfo) && $requestInfo instanceof RequestInfoInterface) {
            $this->requestInfo = $requestInfo;
        } else {
            $this->requestInfo = new RequestInfo($_POST, '', $_FILES, $_SERVER);
        }
    }


    public function init()
    {
        if ($this->isInit === true) {
            // 链式调用
            return $this;
        }
        // 标记已经初始化
        $this->isInit = true;
        // 基础参数初始化
        $this->baseSignInit();
        // 链式调用
        return $this;
    }

    protected function baseSignInit()
    {
        // 标记已经初始化
        $this->isInit = true;
        // 不是头请求就需要验证长度
        $this->isValidationContentLength = !$this->isHeadRequest();
        // 试图获取长度
        $contentLength = $this->getHeader('content-length');
        // 如果没有传入可以理解为0
        if ($contentLength === null || $contentLength === '') {
            $contentLength = 0;
        }
        // 如果不需要验证长度就直接检验通过，否则必须是数字
        $this->isPassContentLength = !$this->isValidationContentLength || is_numeric($contentLength);
        // 取得内容长度
        $this->contentLength = $this->isPassContentLength ? intval($contentLength) : 0;
        // 取得请求头传入的内容md5
        $headerContentMd5 = $this->getHeaderContentMd5();
        // 取得计算出的内容md5
        $inputContentMd5Hex = $this->getInputContentMd5Hex();
        // 取得计算出的内容Base64的md5
        $inputContentMd5Base64 = $this->getInputContentMd5Base64();

        // 既然需要验证长度，然后长度大于0，也就是传入了请求体，也就需要验证md5
        if ($this->isValidationContentLength && $this->contentLength > 0) {
            // 需要检验请求体的MD5
            $this->isValidationContentMd5 = true;
            // 如果请求头没有传入 内容Md5
            if (empty($headerContentMd5) || (empty($inputContentMd5Base64) && empty($inputContentMd5Hex))) {
                $this->isPassContentMd5 = false;
            } elseif ($inputContentMd5Base64 === $headerContentMd5 || $inputContentMd5Hex === $headerContentMd5) {
                $this->isPassContentMd5 = true;
            } else {
                $this->isPassContentMd5 = false;
            }
        } else {
            // 不需要检验 内容Md5，直接通过
            $this->isPassContentMd5 = true;
            // 不需要检验 内容Md5
            $this->isValidationContentMd5 = false;
        }

        return $this;
    }

    /**
     * 判断是否需要验证内容Md5通过
     * @return bool
     */
    public function isPassContentMd5()
    {
        return $this->init()->isPassContentMd5;
    }

    /**
     * 判断是否需要验证内容Md5
     * @return bool
     */
    public function isValidationContentMd5()
    {
        return $this->init()->isValidationContentMd5;
    }

    /**
     * 判断是否需要验证内容长度
     * @return bool
     */
    public function isValidationContentLength()
    {
        return $this->init()->isValidationContentLength;
    }

    /**
     * 判断是否需要验证内容长度
     * @return bool
     */
    public function isPassContentLength()
    {
        return $this->init()->isPassContentLength;
    }

}
