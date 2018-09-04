<?php
/**
 * Created by PhpStorm.
 * User: sicmouse
 * Date: 2018/9/1
 * Time: 下午10:31
 */

namespace DdvPhp\DdvRestfulApi\Abstracts;

use DdvPhp\DdvUrl;
use DdvPhp\DdvAuth\Sign;
use DdvPhp\DdvRestfulApi\Util\Auth as DdvAtuh;
use DdvPhp\DdvRestfulApi\Exception\AuthError as AuthErrorException;
use DdvPhp\DdvRestfulApi\Interfaces\RequestSignInfo as RequestSignInfoInterface;
use DdvPhp\DdvRestfulApi\Interfaces\AuthSign as AuthSignInterface;
use DdvPhp\DdvRestfulApi\Interfaces\Auth;

abstract class AuthSign implements AuthSignInterface
{

    /**
     *
     * @var Auth|null
     */
    protected $auth = null;
    protected $authDatas = array();
    protected $authDataDriverClass = '';
    protected $authDataDriverObj = null;
    protected $authDataDriverConfig = array();
    protected $authorization = null;
    protected $accessKeyId = null;
    /**
     * @var RequestSignInfoInterface $signInfo
     */
    protected $signInfo = null;
    protected $regSessionCard = '/^([\da-f]{4}-[\da-f]{8}-[\da-f]{4}-[\da-f]{4}-[\da-f]{4}-[\da-f]{12}-[\da-f]{8})$/i';

    public function __destruct()
    {
        $this->authDataDriverClose();
    }

    public function __construct(Auth $auth)
    {
        $this->auth = $auth;
        $this->signInfo = $auth->getRequestSignInfo();
        $this->authDataDriverClassInit($auth->getAuthDataDriver());
        $this->authDataDriverConfig = $auth->getAuthDataDriverConfig();
        $this->authorization = $auth->getAuthorization();
    }

    //判断session_card
    public function isSessionCard($card_id = '')
    {
        return (boolean)preg_match($this->regSessionCard, $card_id);
    }

    //生成session_card
    public function createSessionCard()
    {
        $ua = $this->signInfo->getHeader('user-agent');
        $ua = empty($ua) ? microtime() : $ua;
        $session_card = strtolower(substr(md5(uniqid(mt_rand(), true)), 15, 4) . '-' . Sign::createGuid() . '-' . substr(md5($ua), 15, 8));
        $session_card = str_replace(substr($session_card, 13, 6), '-5555-', $session_card);
        return $session_card;
    }

    //生成session_key
    public function createSessionKey($session_card = null)
    {
        $session_card = empty($session_card) ? $this->createSessionCard() : $session_card;
        $ua = $this->signInfo->getHeader('user-agent');
        $ua = empty($ua) ? microtime() : $ua;
        $session_key = strtolower(
            substr(md5($this->createSessionCard() . $session_card), 7, 4)
            . '-' . substr(md5($this->createSessionCard() . mt_rand() . $session_card . Sign::createGuid()), 7, 12)
            . '-' . substr(md5(uniqid(mt_rand(), true)), 15, 4) . '-' . Sign::createGuid() . '-' . substr(md5($ua), 15, 8)
            . '-' . substr(md5(uniqid(mt_rand(), true) . $session_card), 7, 12)
            . '-' . substr(md5(mt_rand() . $session_card), 7, 4)
            . '-' . substr(md5($session_card . mt_rand() . $session_card), 7, 8)
            . '-' . substr(md5($ua . mt_rand() . $ua . $session_card), 7, 4)
        );
        return $session_key;
    }

    public function generateSessionId()
    {
        if ($this->authDataDriverObj && method_exists($this->authDataDriverObj, 'generateSessionId')) {
            return $this->authDataDriverObj->generateSessionId();
        }
        $sidLength = @ini_get('session.sid_length');
        $sidLength = !isset($sidLength) || intval($sidLength) <= 8 ? 32 : $sidLength;
        $randomSid = bin2hex(random_bytes($sidLength));
        // Use same charset as PHP
        $sessionId = '';
        if (!$sidLength > 12) {
            $server = $this->signInfo->getServers();

            $pp = isset($server['REMOTE_ADDR']) ? $server['REMOTE_ADDR'] : microtime();
            $pp .= isset($server['HTTP_CLIENT_IP']) ? $server['HTTP_CLIENT_IP'] : microtime();
            $pp .= isset($server['HTTP_X_FORWARDED_FOR']) ? $server['HTTP_X_FORWARDED_FOR'] : microtime();
            $sessionId = substr(md5(microtime() . $pp . mt_rand() . Sign::createGuid()), 0, 3);
            $sessionId .= substr(md5($pp . microtime() . mt_rand() . $sidLength . $randomSid), 0, 3);
            $sessionId .= substr(md5(microtime() . $pp . mt_rand() . $pp . $this->createSessionKey() . $sessionId), 0, 3);
            $sessionId .= substr(md5(microtime() . $sessionId . mt_rand() . $pp . $randomSid), 0, 3);
        }
        $sessionId .= substr(rtrim(strtr($randomSid, '+/', ',-'), '='), 0, $sidLength - ($sidLength > 12 ? 12 : 0));
        $sessionId = substr($sessionId, 0, $sidLength);

        if ($this->getAuthData($sessionId) !== null) {
            return $this->generateSessionId();
        }
        return $sessionId;
    }

    public function getAccessKeyId(){
        return $this->accessKeyId;
    }
    public function getAuthData($accessKeyId)
    {
        if (isset($authDatas[$accessKeyId])) {
            return $authDatas[$accessKeyId];
        }
        // 读取数据
        $res = $this->getAuthDataDriver()->read($accessKeyId);
        // 反序列化并且返回
        $authDatas[$accessKeyId] = empty($res) ? null : unserialize($res);
        return $authDatas[$accessKeyId];
    }

    public function saveAuthData($accessKeyId, $data = null)
    {
        $authDatas[$accessKeyId] = $data;
        // 序列化数组
        $res = serialize($data);
        // 保存数据
        $res = $this->getAuthDataDriver()->write($accessKeyId, $res);
    }

    public function getAuthDataDriver()
    {
        if ($this->authDataDriverObj && method_exists($this->authDataDriverObj, 'close')) {
            return $this->authDataDriverObj;
        }
        $this->authDataDriverObj = new $this->authDataDriverClass();
        // 打开连接
        $this->authDataDriverObj->open($this->authDataDriverConfig);
        return $this->authDataDriverObj;
    }

    public function authDataDriverClose()
    {
        if (!($this->authDataDriverObj && method_exists($this->authDataDriverObj, 'close'))) {
            return;
        }
        // 打开连接
        $this->authDataDriverObj->close();
        $this->authDataDriverObj = null;
    }

    public function getSignHeaders($signHeaderKeys = array())
    {
        $signHeaders = array();
        $signSysHeadersKeys = $this->signInfo->getSysHeadersKeys();
        $signHeaderKeysLower = [];
        foreach ($signHeaderKeys as $key) {
            $signHeaderKeysLower[] = strtolower(str_replace('-', '_', $key));
            $value = $this->signInfo->getHeader($key);
            if ($value !== null && is_string($value)) {
                $signHeaders[$key] = $value;
            } else {
                throw new AuthErrorException('I did not find your authorization header[' . $key . ']', 'AUTHORIZATION_HEADERS_NOT_FIND', 403);
            }
        }
        // 遍历所有获取到的，但是没有签名的
        foreach ($this->signInfo->getXHeadersKeys() as $key) {
            // 匹配的时候，需要把键名的-替换为_ 因为php的特殊原因
            if (!in_array(strtolower(str_replace('-', '_', $key)), $signHeaderKeysLower)) {
                throw new AuthErrorException('The following header information you have not authenticated[' . $key . ']', 'AUTHORIZATION_HEADERS_X_NOT_ALL_SIGNATURES', 403);
            }
        }
        // 遍历所有获取到的，但是没有签名的
        foreach ($this->signInfo->getSysHeadersKeys() as $key) {
            $keyt = strtolower(str_replace('-', '_', $key));
            // 匹配的时候，需要把键名的-替换为_ 因为php的特殊原因
            if (!in_array($keyt, $signHeaderKeysLower)) {
                // 如果不需要检验请求体正确性
                if (!$this->signInfo->isValidationContentMd5()) {
                    // 就不检验以下字段的缺失
                    if (in_array($keyt, ['content_md5', 'content_type', 'content_length'])) {
                        continue;
                    }
                }
                throw new AuthErrorException('The following header information you have not authenticated[' . $key . ']', 'AUTHORIZATION_HEADERS_SYS_NOT_ALL_SIGNATURES', 403);
            }
        }
        return $signHeaders;
    }

    // 获取已经索取的数据信息
    public function getSignUrlByUrl($accessKeyId = null, $url = '/', $noSignQuery = array(), $method = 'GET', $query = array(), $headers = array(), $authClassName = null)
    {
        $urlObj = DdvUrl::parse($url);
        if ($urlObj['query']) {
            $params = DdvUrl::parseQuery($urlObj['query']);
            $params = is_array($params) ? $params : array();
            $params = array_merge($params, $query);
            $path = self::getSignUrl($accessKeyId, $urlObj['path'], $params, $noSignQuery, $method, $headers, $authClassName);
            $index = strpos($path, '?');
            if ($index !== false) {
                $urlObj['path'] = substr($path, 0, $index);
                $urlObj['query'] = substr($path, $index + 1);
            } else {
                $urlObj['path'] = $path;
            }
        }
        return DdvUrl::build($urlObj);
    }

    public function getSignUrl($accessKeyId = null, $path = '/', $query = array(), $noSignQuery = array(), $method = 'GET', $headers = array(), $authClassName = null)
    {
        if (empty($accessKeyId)) {
            throw new AuthErrorException('session id must input', 'MUST_INPUT_SESSION_ID', 400);
        }
        $authData = $this->auth->getAuthData($accessKeyId);
        if (empty($authData)) {
            throw new AuthErrorException('auth data not find', 'AUTH_DATA_NOT_FIND', 400);
        }
        if (empty($authClassName)) {
            $authClassName = \DdvPhp\DdvRestfulApi\Auth\AuthSignDdvUrlV1::class;
        }
        if (!class_exists($authClassName)) {
            throw new AuthErrorException('Authentication Version Class Not Find', 'AUTHENTICATION_VERSION_CLASS_NOT_FIND', 400);
        }
        if (!method_exists($authClassName, 'getSignUrl')) {
            throw new AuthErrorException('Authentication Version Class Not support getSignUrl', 'AUTHENTICATION_VERSION_CLASS_NOT_SUPPORT_GET_SIGN_URL', 400);
        }
        return $authClassName::getSignUrl($accessKeyId, $authData, $path, $query, $noSignQuery, $method, $headers, $authClassName);
    }

    public function checkContentMd5True()
    {
        if (!$this->signInfo->isPassContentMd5()) {
            throw new AuthErrorException('Content Md5 Error', 'CONTENT_MD5_ERROR', 403);
        }
    }

    public function checkContentLengthTrue()
    {
        if (!$this->signInfo->isPassContentLength()) {
            throw new AuthErrorException('Content Length Error', 'CONTENT_LENGTH_ERROR', 403);
        }
    }


    protected function authDataDriverClassInit($authDataDriverInput)
    {
        // 默认是这种模式查找
        $authDataDriver = $authDataDriverInput;
        if (!class_exists($authDataDriver)) {
            $authDataDriver = '\\DdvPhp\\DdvRestfulApi\\AuthData\\AuthData' . ucfirst($authDataDriverInput) . 'Driver';
        }
        if (!class_exists($authDataDriver)) {
            $authDataDriver = '\\' . $authDataDriverInput;
        }
        if (!class_exists($authDataDriver)) {
            throw new AuthErrorException('authDataDriver Class Not Find', 'AUTHDATADRIVER_CLASS_NOT_FIND', '500');
        }

        $this->authDataDriverClass = $authDataDriver;
        unset($authDataDriver, $authDataDriverInput);
    }
}
