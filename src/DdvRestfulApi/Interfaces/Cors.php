<?php
/**
 * Created by PhpStorm.
 * User: sicmouse
 * Date: 2018/9/3
 * Time: 上午10:06
 */

namespace DdvPhp\DdvRestfulApi\Interfaces;

use DdvPhp\DdvRestfulApi\Exception\RequestParseError;

interface Cors
{
    public function isResponseOnlyHeader();

    public function getResponseHeaders();

    public function setRequestInfo(RequestInfo $requestInfo);

    public function setConfig($config = array());

    public function checkAllow();
}
