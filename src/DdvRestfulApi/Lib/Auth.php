<?php
/**
 * Created by PhpStorm.
 * User: sicmouse
 * Date: 2018/9/3
 * Time: 下午7:55
 */

namespace DdvPhp\DdvRestfulApi\Lib;

use DdvPhp\DdvRestfulApi\Interfaces\AuthSign;
use DdvPhp\DdvRestfulApi\Interfaces\RequestSignInfo;
use DdvPhp\DdvRestfulApi\Interfaces\Auth as AuthInterface;
use DdvPhp\DdvRestfulApi\Exception\AuthError as AuthErrorException;

class Auth implements AuthInterface
{
    protected $authDataDriver = 'file';
    protected $authDataDriverConfig = array();
    /**
     * 授权前面类目录
     * @var array
     */
    protected $authSignClassNamespaces = array();
    /**
     * @var RequestSignInfo|null
     */
    protected $signInfo = null;
    /**
     * 授权版本
     * @var string|null
     */
    protected $version = null;
    /**
     * 授权内容
     * @var string|null
     */
    protected $authorization = null;
    /**
     * 授权具体版本的签名对象
     * @var AuthSign|null
     */
    protected $authSign = null;

    /**
     * 设置请求信息
     * @param RequestInfo $requestInfo
     * @return $this
     * @throws RJsonError
     */
    public function setRequestSignInfo(RequestSignInfo $signInfo)
    {
        if (isset($signInfo) && $signInfo instanceof RequestSignInfo) {
            $this->signInfo = $signInfo;
        } else {
            throw new RJsonError('requestInfo is wrong');
        }
        return $this;
    }

    /**
     * 获取授权数据
     * @param $sessionId
     * @return array
     */
    public function getAuthData($sessionId)
    {
        return $this->getAuthSign()->getAuthData($sessionId);
    }

    /**
     * 设置授权数据的存储
     * @param $sessionId
     * @param null $data
     * @return null
     */
    public function saveAuthData($sessionId, $data = null)
    {
        return $this->getAuthSign()->saveAuthData($sessionId, $data);
    }

    /**
     * 获取签名后的url
     * @param null $sessionId
     * @param string $path
     * @param array $query
     * @param array $noSignQuery
     * @param string $method
     * @param array $headers
     * @param null $authClassName
     * @return string
     */
    public function getSignUrl($sessionId = null, $path = '/', $query = array(), $noSignQuery = array(), $method = 'GET', $headers = array(), $authClassName = null)
    {
        return $this->getAuthSign()->getSignUrl($sessionId, $path, $query, $noSignQuery, $method, $headers, $authClassName);
    }

    /**
     * 对url进行签名
     * @param null $sessionId
     * @param string $url
     * @param array $noSignQuery
     * @param string $method
     * @param array $query
     * @param array $headers
     * @param null $authClassName
     * @return string
     */
    public function getSignUrlByUrl($sessionId = null, $url = '/', $noSignQuery = array(), $method = 'GET', $query = array(), $headers = array(), $authClassName = null)
    {
        return $this->getAuthSign()->getSignUrlByUrl($sessionId, $url, $noSignQuery, $method, $query, $headers, $authClassName);
    }

    public function sign()
    {
        $signInfo = $this->getRequestSignInfo();
        /**
         * 获取授权信息
         * 通过 authorization 头
         */
        $authorization = trim($signInfo->getAuthorization());
        /**
         * 试图查找/，判断是否是合法的授权信息
         */
        $start = strpos($authorization, '/');
        //没有找到就不合法
        if ($start === false) {
            throw new AuthErrorException('Authentication Format Error', 'AUTHENTICATION_FORMAT_ERROR', '403');
        }
        //提取版本
        $this->version = substr($authorization, 0, $start);
        //提取授权信息
        $this->authorization = substr($authorization, ($start + 1));
        if (empty($this->version)) {
            throw new AuthErrorException('Authentication Version Error', 'AUTHENTICATION_VERSION_ERROR', '403');
        } elseif (empty($this->authorization)) {
            throw new AuthErrorException('Authentication Info Error', 'AUTHENTICATION_INFO_ERROR', '403');
        }
        /**
         * 设置授权版本
         */
        // 中杠转驼峰
        $className = 'AuthSign' . ucfirst(preg_replace_callback(
                '(\-\w)',
                function ($matches) {
                    return strtoupper(substr($matches[0], 1));
                },
                $this->version
            ));
        /**
         * 强制是一个数组
         */
        if (!is_array($this->authSignClassNamespaces)) {
            $this->authSignClassDirs = array();
        }
        /**
         * 压入一个类前缀
         */
        $this->authSignClassNamespaces[] = '\\DdvPhp\\DdvRestfulApi\\Auth\\';
        /**
         * 循环查找存在的类
         */
        foreach ($this->authSignClassNamespaces as $namespace) {
            if (class_exists($namespace . $className)) {
                // 加入命名空间
                $className = $namespace . $className;
            }
        }
        /**
         * 判断这个是否找到
         */
        if (!class_exists($className)) {
            throw new AuthErrorException('Authentication Version Class Not Find', 'AUTHENTICATION_VERSION_CLASS_NOT_FIND', '403');
        }
        /**
         * 实例化该文件
         * @var $a2 授权信息
         * @var  授权信息
         * @var AuthSign $this ->authSign
         */
        $this->authSign = new $className($this);

        /**
         * 签名
         */
        $this->getAuthSign()->sign();
        //卸载
        unset($start, $authorization, $signInfo, $className, $authObj);
    }

    public function setConfig($config = array())
    {
        if (!empty($config['authDataDriver'])) {
            $this->authDataDriver = $config['authDataDriver'];
        }
        if (!empty($config['authDataDriverConfig'])) {
            $this->authDataDriverConfig = $config['authDataDriverConfig'];
        }
        if (empty($this->authDataDriverConfig)) {
            $this->authDataDriverConfig = array();
        }
    }

    /**
     * @return authSign
     * @throws AuthErrorException
     */
    public function getAuthSign()
    {
        if (isset($this->authSign) && $this->authSign instanceof AuthSign) {
            return $this->authSign;
        } else {
            throw new AuthErrorException('Auth authentication must be performed first', 'MUST_RUN_AUTH_VERIFICATION', 500);
        }
    }

    /**
     * 获取会话id
     * @return string
     * @throws AuthErrorException
     */
    public function getAccessKeyId()
    {
        return $this->getAuthSign()->getAccessKeyId();
    }
    /**
     * 获取会话id
     * @return string
     * @throws AuthErrorException
     */
    public function getSessionId()
    {
        return $this->getAccessKeyId();
    }

    /**
     * @return string
     */
    public function getAuthDataDriver()
    {
        return $this->authDataDriver;
    }

    /**
     * @return array
     */
    public function getAuthDataDriverConfig()
    {
        return $this->authDataDriverConfig;
    }

    /**
     * @return RequestSignInfo
     */
    public function getRequestSignInfo()
    {
        if ($this->signInfo instanceof RequestSignInfo) {
            return $this->signInfo;
        } else {
            throw new AuthErrorException('No RequestSignInfo instance', 'NO_REQUEST_SIGN_INFO_INSTANCE', 500);
        }
    }

    /**
     * 获取授权签名信息
     * @return null|string
     */
    public function getAuthorization()
    {
        if (empty($this->authorization)) {
            throw new AuthErrorException('Authentication Info Length Error', 'AUTHORIZATION_ERROR_INFO_LENGTH', 403);
        }
        return is_string($this->authorization) ? $this->authorization : '';
    }

    /**
     * 获取授权版本
     * @return null|string
     */
    public function getVersion()
    {
        return $this->version;
    }
}
