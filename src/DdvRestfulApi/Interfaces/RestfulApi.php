<?php
/**
 * Created by PhpStorm.
 * User: sicmouse
 * Date: 2018/9/4
 * Time: 下午1:32
 */

namespace DdvPhp\DdvRestfulApi\Interfaces;


interface RestfulApi extends Auth
{
    /**
     * @return RequestInfo
     */
    public function getRequestInfo();
    /**
     * @param RequestInfo $requestInfo
     * @return $this|RestfulApi
     */
    public function setRequestInfo(RequestInfo $requestInfo);

    /**
     * @return Auth
     */
    public function getAuth();

}