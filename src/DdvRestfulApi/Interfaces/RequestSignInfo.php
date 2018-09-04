<?php

namespace DdvPhp\DdvRestfulApi\Interfaces;

interface RequestSignInfo extends RequestInfo
{
    // 判断是否需要检验 内容md5
    public function isValidationContentMd5();

    public function isPassContentMd5();

    public function isPassContentLength();

    /**
     * @return RequestInfo
     */
    public function getRequestInfo();
}
