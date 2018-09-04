<?php
/**
 * Created by PhpStorm.
 * User: sicmouse
 * Date: 2018/9/4
 * Time: 下午1:37
 */

namespace DdvPhp\DdvRestfulApi\Lib;

use DdvPhp\DdvRestfulApi\AuthData\AuthDataSessionDriver;
use DdvPhp\DdvRestfulApi\Exception\RequestParseError;
use DdvPhp\DdvRestfulApi\Interfaces\Auth as AuthInterface;
use DdvPhp\DdvRestfulApi\Interfaces\AuthSign as AuthSignInterface;
use DdvPhp\DdvRestfulApi\Interfaces\RestfulApi as RestfulApiInterface;
use DdvPhp\DdvRestfulApi\Interfaces\RequestInfo as RequestInfoInterface;
use DdvPhp\DdvRestfulApi\Interfaces\RequestSignInfo as RequestSignInfoInterface;

class RestfulApi implements RestfulApiInterface
{
    /**
     * @var AuthInterface|null
     */
    protected $auth = null;
    /**
     * @var AuthSignInterface|null
     */
    protected $authSign = null;
    /**
     * @var RequestInfoInterface|null
     */
    protected $requestInfo = null;
    /**
     * @var RequestSignInfoInterface|null
     */
    protected $requestSignInfo = null;
    /**
     * @var string|null
     */
    protected $authDataDriver = null;
    /**
     * @var string|null
     */
    protected $authHeadersPrefix = null;
    /**
     * @return RequestInfo
     */
    public function getRequestInfo()
    {
        if (isset($this->requestInfo)&&$this->requestInfo instanceof RequestInfoInterface){
            return $this->requestInfo;
        }
        if (isset($this->requestSignInfo)&&$this->requestSignInfo instanceof RequestSignInfoInterface){
            $this->setRequestInfo($this->requestSignInfo->getRequestInfo());
            return $this->requestInfo;
        }
        throw new RequestParseError('Not Request Parse', 'NOT_REQUEST_PARSE');
    }
    /**
     * @param RequestInfo $requestInfo
     * @return $this|RestfulApi
     */
    public function setRequestInfo(RequestInfoInterface $requestInfo)
    {
        $this->requestInfo = $requestInfo;
    }
    /**
     * @return RequestSignInfo
     */
    public function getRequestSignInfo()
    {
        if (isset($this->requestSignInfo)&&$this->requestSignInfo instanceof RequestSignInfoInterface){
            return $this->requestSignInfo;
        }
        if (isset($this->requestInfo)&&$this->requestInfo instanceof RequestInfoInterface){
            $this->setRequestSignInfo(new RequestSignInfo());
            $this->requestSignInfo->createRequestInfo($this->requestInfo);
            return $this->requestSignInfo;
        }
        throw new RequestParseError('Not Request Parse', 'NOT_REQUEST_PARSE');
    }
    /**
     * @param RequestSignInfo $requestSignInfo
     * @return $this|RestfulApi
     */
    public function setRequestSignInfo(RequestSignInfoInterface $requestSignInfo){
        $this->requestSignInfo = $requestSignInfo;
    }
    public function setConfig($config = array())
    {
        $this->authDataDriver = empty($config['authDataDriver']) ? AuthDataSessionDriver::class : $config['authDataDriver'];
    }
    public function sign(){
        $auth = $this->getAuth();
        $auth->setConfig(array(
                'authDataDriver' => $this->authDataDriver,
            )
        );
        $auth->sign();

    }
    /**
     * @return Auth
     */
    public function getAuth()
    {
        if (isset($this->auth) && $this->auth instanceof AuthInterface){
            return $this->auth;
        }
        /**
         * @var RequestSignInfoInterface $requestSignInfo
         */
        $requestSignInfo = $this->getRequestSignInfo();
        /**
         * @var AuthInterface $this->auth
         */
        $this->auth = new Auth();
        $this->auth->setRequestSignInfo($requestSignInfo);
        return $this->auth;
    }
    /**
     * @return AuthSign
     */
    public function getAuthSign()
    {
        return $this->getAuth()->getAuthSign();
    }
    /**
     * 获取授权版本
     * @return string
     */
    public function getVersion(){
        return $this->getAuth()->getVersion();
    }
    /**
     * @return string
     */
    public function getAccessKeyId(){
        return $this->getAuth()->getAccessKeyId();
    }

    /**
     * 获取授权数据
     * @param $sessionId
     * @return array
     */
    public function getAuthData($sessionId){
        return $this->getAuth()->getAuthData($sessionId);
    }

    /**
     * 获取授权签名信息
     * @return string
     */
    public function getAuthorization(){
        return $this->getAuth()->getAuthorization();
    }

    /**
     * 获取授权数据驱动类
     * @return string
     */
    public function getAuthDataDriver(){
        return $this->getAuth()->getAuthDataDriver();
    }
    /**
     * 获取授权数据驱动配置数据
     * @return array
     */
    public function getAuthDataDriverConfig(){
        return $this->getAuth()->getAuthDataDriverConfig();
    }
    /**
     * 设置授权数据的存储
     * @param $sessionId
     * @param null $data
     * @return null
     */
    public function saveAuthData($sessionId, $data = null){
        return $this->getAuth()->saveAuthData($sessionId, $data);
    }
    /**
     * 获取会话id
     * @return string
     */
    public function getSessionId(){
        return $this->getAuth()->getSessionId();
    }

    /**
     * @param null $sessionId
     * @param string $path
     * @param array $query
     * @param array $noSignQuery
     * @param string $method
     * @param array $headers
     * @param null $authClassName
     * @return string
     */
    public function getSignUrl($sessionId = null, $path = '/', $query = array(), $noSignQuery = array(), $method = 'GET', $headers = array(), $authClassName = null){
        return $this->getAuth()->getSignUrl($sessionId, $path, $query, $noSignQuery, $method, $headers, $authClassName);
    }

    /**
     * @param null $sessionId
     * @param string $url
     * @param array $noSignQuery
     * @param string $method
     * @param array $query
     * @param array $headers
     * @param null $authClassName
     * @return string
     */
    public function getSignUrlByUrl($sessionId = null, $url = '/', $noSignQuery = array(), $method = 'GET', $query = array(), $headers = array(), $authClassName = null){
        return $this->getAuth()->getSignUrl($sessionId, $url, $noSignQuery, $method, $query, $headers, $authClassName);
    }
}