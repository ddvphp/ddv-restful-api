<?php

namespace DdvPhp\DdvRestfulApi\Auth;

use DdvPhp\DdvAuth\Sign;
use DdvPhp\DdvAuth\AuthSha256;
use DdvPhp\DdvRestfulApi\Abstracts\AuthSign;
use DdvPhp\DdvRestfulApi\Interfaces\AuthSign as AuthSignInterface;
use DdvPhp\DdvRestfulApi\Exception\AuthError as AuthErrorException;

/**
 *
 */
class AuthSignDdvAuthV1 extends AuthSign implements AuthSignInterface
{
    private $sessionCard = null;
    private $requestId = null;
    private $regAuth =
        '/^([\da-f]{8}-[\da-f]{4}-[\da-f]{4}-[\da-f]{4}-[\da-f]{12})\/([0-9a-zA-Z,-]+)\/([\da-f]{4}-[\da-f]{8}-[\da-f]{4}-[\da-f]{4}-[\da-f]{4}-[\da-f]{12}-[\da-f]{8})\/([\d]{4}-[\d]{2}-[\d]{2}T[\d]{2}:[\d]{2}:[\d]{2}Z)\/(\d+)\/([\w\-\;]+|)\/([\da-f]{64})$/i';

    public function sign()
    {
        // 试图旧授权信息
        $this->checkAuth();
    }

    // 判断是否通过该授权通过的
    public function is()
    {
        return !empty($this->accessKeyId);
    }

    public function getRequestId()
    {
        return $this->requestId;
    }

    public function getSessionCard()
    {
        return $this->sessionCard;
    }

    private function checkAuth()
    {
        // 检测内容md5有没有问题
        $this->checkContentMd5True();
        // 内容长度有没有问题
        $this->checkContentLengthTrue();

        $auths = array();
        // 试图正则匹配
        preg_match($this->regAuth, trim($this->authorization), $auths);
        //
        if (count($auths) !== 8) {
            throw new AuthErrorException('Authentication Info Length Error', 'AUTHORIZATION_ERROR_INFO_LENGTH', 403);
        } elseif (empty($auths[0])) {
            //抛出授权信息格式异常
            throw new AuthErrorException('Authentication wrong format as content', 'AUTHORIZATION_ERROR_FORMAT_WRONG', 403);
        }
        list(
            ,
            // 请求id
            $requestId,
            // 会话id
            $sessionId,
            // 设备id
            $sessionCard,
            // 签名时间
            $signTimeString,
            // 过期时间
            $expiredTimeOffset,
            // 需要签名的头的key
            $signHeaderKeysStr,
            // 客户端签名
            $clientSign
            ) = $auths;
        // 授权数据
        $auth = new AuthSha256();

        try {
            // 签名时间 , 签名过期时间, 检查签名时间
            $auth->setSignTimeString($signTimeString)->setExpiredTimeOffset($expiredTimeOffset)->checkSignTime();
        } catch (\DdvPhp\DdvException\Error $e) {
            throw new AuthErrorException($e->getMessage(), $e->getErrorId(), $e->getCode());
        }


        $data = $this->getAuthData($sessionId);
        if ($sessionCard !== $data['card']) {
            throw new AuthErrorException('session card Error!', 'AUTHORIZATION_SESSION_CARD_NOT_SELF', 403);
        }
        // 会话id
        $auth->setAccessKeyId($sessionId)->setAccessKey($data['key'])->setMethod($this->signInfo->getMethod());

        //获取请求的uri
        $requestURI = $this->signInfo->getUri();

        $authVersion = $this->auth->getVersion();

        // 请求uri, 签名版本
        $auth->setUri($requestURI)->setAuthVersion($authVersion);

        // 请求id, 设备id
        $auth->setRequestId($requestId)->setDeviceCard($sessionCard);

        // 获取签名key
        $signHeaderKeys = Sign::getHeaderKeysByStr($signHeaderKeysStr);
        // 获取签名头
        $signHeaders = $this->getSignHeaders($signHeaderKeys);

        $auth->setHeaders($signHeaders);
        // 获取签名数据
        $authArray = $auth->getAuthArray();
        //签名通过，接下来检测content_md5
        if ($authArray['sign'] !== $clientSign) {
            $errorData = array('debugSign' => $authArray);
            $errorData['debugSign']['sign.clien'] = $clientSign;
            $errorData['debugSign']['sign.server'] = $authArray['sign'];
            throw new AuthErrorException('Signature authentication failure', 'AUTHORIZATION_SIGNATURE_FAILURE', 403, $errorData);
        }
        $this->requestId = $requestId;
        $this->accessKeyId = $sessionId;
        $this->sessionCard = $sessionCard;
        return true;
    }
}
