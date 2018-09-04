<?php
/**
 * Created by PhpStorm.
 * User: sicmouse
 * Date: 2018/9/1
 * Time: 下午4:01
 */

namespace DdvPhp\DdvRestfulApi\Abstracts;

use \DdvPhp\DdvRestfulApi\Exception\RequestParseError;
use \DdvPhp\DdvRestfulApi\Interfaces\RequestInfo;
use \DdvPhp\DdvRestfulApi\Interfaces\RequestHeaders;
use \DdvPhp\DdvRestfulApi\Interfaces\RequestSignInfo as RequestSignInfoInterface;


abstract class RequestSignInfo implements RequestHeaders, RequestInfo, RequestSignInfoInterface
{
    /**
     * @var RequestInfo|null $requestInfo
     */
    protected $requestInfo = null;
    /**
     * @return RequestInfo
     */
    public function getRequestInfo(){
        if (!($this->requestInfo instanceof RequestInfo)){
            $this->createRequestInfo();
        }
        if ($this->requestInfo instanceof RequestInfo){
            return $this->requestInfo;
        }else{
            throw new RequestParseError('No RequestInfo instance', 'NO_REQUESTINFO_INSTANCE', 500);
        }
    }

    /**
     * @param RequestInfo|null $requestInfo
     * @return mixed
     */
    public abstract function createRequestInfo(RequestInfo $requestInfo = null);
    public function getContentHash($key, $type = 'hex'){
        return $this->getRequestInfo()->getContentHash($key, $type);
    }
    public function setContentHash($data = array())
    {
        $this->getRequestInfo()->setContentHash($data);
        return $this;
    }
    public function getHeaderContentMd5(){
        return $this->getRequestInfo()->getHeaderContentMd5();
    }
    public function getInputContentMd5Hex(){
        return $this->getRequestInfo()->getInputContentMd5Hex();
    }
    public function getInputContentMd5Base64(){
        return $this->getRequestInfo()->getInputContentMd5Base64();
    }
    public function getMethod(){
        return $this->getRequestInfo()->getMethod();
    }
    public function isHeadRequest(){
        return $this->getRequestInfo()->isHeadRequest();
    }
    public function getQuerys(){
        return $this->getRequestInfo()->getQuerys();
    }
    public function getParameters(){
        return $this->getRequestInfo()->getParameters();
    }
    public function getFiles(){
        return $this->getRequestInfo()->getFiles();
    }
    public function getCookies(){
        return $this->getRequestInfo()->getCookies();
    }
    public function getServers(){
        return $this->getRequestInfo()->getServers();
    }
    public function requestInfoInit($parameters = null, $cookies = '', $files = null, $server = null, $headers = array()){
        return $this->getRequestInfo()->requestInfoInit($parameters, $cookies, $files, $server, $headers);
    }
    public function getHeadersPrefix(){
        return $this->getRequestInfo()->getHeadersPrefix();
    }
    public function setHeadersPrefix($headersPrefix = null){
        $this->getRequestInfo()->setHeadersPrefix($headersPrefix);
        return $this;
    }
    public function getAuthorization(){
        return $this->getRequestInfo()->getAuthorization();
    }
    public function getUri(){
        return $this->getRequestInfo()->getUri();
    }
    public function getHost(){
        return $this->getRequestInfo()->getHost();
    }
    public function getHostName(){
        return $this->getRequestInfo()->getHostName();
    }
    public function getHeader($key){
        return $this->getRequestInfo()->getHeader($key);
    }
    public function getHeaders(){
        return $this->getRequestInfo()->getHeaders();
    }
    public function getXHeaders(){
        return $this->getRequestInfo()->getXHeaders();
    }
    public function getSysHeaders(){
        return $this->getRequestInfo()->getSysHeaders();
    }
    public function getXHeadersKeys(){
        return $this->getRequestInfo()->getXHeadersKeys();
    }
    public function getSysHeadersKeys(){
        return $this->getRequestInfo()->getSysHeadersKeys();
    }
    public function getHeaderKey($key, $headers = null, $defaultValue = null){
        return $this->getRequestInfo()->getHeaderKey($key, $headers, $defaultValue);
    }
    public function setHeaders($headers){
        $this->getRequestInfo()->setHeaders($headers);
        return $this;
    }

}
