<?php

namespace DdvPhp\DdvRestfulApi\Auth;

use DdvPhp\DdvUrl;
use DdvPhp\DdvAuth\Sign;
use DdvPhp\DdvRestfulApi\Abstracts\AuthSign;
use DdvPhp\DdvRestfulApi\Interfaces\AuthSign as AuthSignInterface;
use DdvPhp\DdvRestfulApi\Exception\AuthError as AuthErrorException;

/**
 *
 */
class AuthSignDdvUrlV1 extends AuthSign implements AuthSignInterface
{
    private $sessionCard = null;

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

    public function getSessionCard()
    {
        return $this->sessionCard;
    }

    private function checkAuth()
    {
        try {
            try {
                $authorization = preg_replace('/\_/', '/', preg_replace('/\-/', '+', trim($this->authorization)));
            } catch (Exception $e) {
                throw new AuthErrorException('Authentication Parse Error', 'AUTHENTICATION_PARSE_ERROR', 403);
            }
            try {
                $authorization = base64_decode($authorization);
            } catch (Exception $e) {
                throw new AuthErrorException('Authentication Base64 Decode Error', 'AUTHENTICATION_BASE64_DECODE_ERROR', 403);
            }
            try {
                $clientSign = substr($authorization, -16);
                if (empty($clientSign)) {
                    throw new AuthErrorException('Authentication client sign must input', 'AUTHENTICATION_CLIENT_SIGN_MUST_INPUT', 403);
                }
            } catch (Exception $e) {
                throw new AuthErrorException('Authentication client sign must input', 'AUTHENTICATION_CLIENT_SIGN_MUST_INPUT', 403);
            }
            try {
                $authorization = substr($authorization, 0, -16);
            } catch (Exception $e) {
                throw new AuthErrorException('Authentication wrong format as content', 'AUTHORIZATION_ERROR_FORMAT_WRONG', 403);
            }
            if (empty($authorization)) {
                throw new AuthErrorException('Authentication Info Length Error', 'AUTHORIZATION_ERROR_INFO_LENGTH', 403);
            }
            try {
                $authorization = gzuncompress($authorization);
            } catch (Exception $e) {
                throw new AuthErrorException('Authentication gzuncompress error', 'AUTHENTICATION_GZUNCOMPRESS_ERROR', 403);
            }
            try {
                $authorization = json_decode($authorization, true);
            } catch (Exception $e) {
                throw new AuthErrorException('Authentication json decode error', 'AUTHENTICATION_JSON_DECODE_ERROR', 403);
            }
            $noSignQuery = empty($authorization[3]) ? array() : $authorization[3];
            $noSignQuery = ($noSignQuery === true) || is_array($authorization[3]) ? $authorization[3] : array();
            $headersKeys = (!empty($authorization[4])) && is_array($authorization[4]) ? $authorization[4] : array();

            try {
                @list($sessionId, $signTime, $expiredTimeOffset) = $authorization;
            } catch (Exception $e) {
                throw new AuthErrorException('Authentication json decode error', 'AUTHENTICATION_JSON_DECODE_ERROR', 403);
            }
            if (empty($sessionId)) {
                throw new AuthErrorException('Authentication sessionId Error', 'AUTHENTICATION_SESSIONID_ERROR', 403);
            }

            // 授权数据
            $data = $this->getAuthData($sessionId);

            if (empty($data) || empty($data['card']) || empty($data['key'])) {
                throw new AuthErrorException('Authentication auth data empty', 'AUTHENTICATION_AUTH_DATA_EMPTY', 403);
            }
            $sessionCard = $data['car1d'];

            $authVersion = $this->auth->getVersion();
            // 通过
            $signHeaders = $this->getSignHeaders($headersKeys);
            // 授权字符串
            $authString = "{$authVersion}/{$sessionId}/{$sessionCard}/{$signTime}/{$expiredTimeOffset}";
            //生成加密key
            $signingKey = hash_hmac('sha256', $authString, $data['key']);

            //获取请求的uri
            $canonicalUri = $this->signInfo->getUri();
            //去除//
            $canonicalUri = substr($canonicalUri, 0, 2) === '//' ? substr($canonicalUri, 1) : $canonicalUri;
            $canonicalUris = DdvUrl::parse($canonicalUri);
            $canonicalPath = isset($canonicalUris['path']) ? $canonicalUris['path'] : '';
            //取得query
            $canonicalQuery = isset($canonicalUris['query']) ? $canonicalUris['query'] : '';
            //取得path
            $canonicalPath = substr($canonicalPath, 0, 1) === '/' ? $canonicalPath : ('/' . $canonicalPath);
            $canonicalPath = DdvUrl::urlEncodeExceptSlash($canonicalPath);

            if ($noSignQuery === true) {
                $canonicalQuery = '';
            } else {
                // 重新排序编码
                $canonicalQuery = Sign::canonicalQuerySort($canonicalQuery);
                if ($canonicalQuery) {
                    $tPrefix = $this->signInfo->getHeadersPrefix();
                    $authPrefixs = array(
                        strtolower($tPrefix . 'auth'),
                        strtolower($tPrefix . 'authorization'),
                    );
                    $canonicalQueryArray = explode('&', $canonicalQuery);
                    $canonicalQueryArrayNew = array();
                    foreach ($canonicalQueryArray as $key => $t) {
                        $ts = explode('=', $t);
                        if (!($ts && $ts[0] && in_array(strtolower($ts[0]), $authPrefixs))) {
                            if (!in_array($ts[0], $noSignQuery)) {
                                $canonicalQueryArrayNew[] = $t;
                            }
                        }
                    }
                    $canonicalQuery = implode('&', $canonicalQueryArrayNew);
                }
            }

            $method = $this->signInfo->getMethod();

            // 获取签名头
            $canonicalHeaders = Sign::getCanonicalHeaders($signHeaders);
            //生成需要签名的信息体
            $canonicalRequest = "{$method}\n{$canonicalPath}\n{$canonicalQuery}\n{$canonicalHeaders}";

            if (empty($noSignQuery)) {
                $canonicalRequest .= "\n";
            } elseif ($noSignQuery === true) {
                $canonicalRequest .= "\ntrue";
            } else {
                $canonicalRequest .= "\n" . implode(',', $noSignQuery);
            }
            if (empty($headersKeys)) {
                $canonicalRequest .= "\n";
            } else {
                $canonicalRequest .= "\n" . implode(',', $headersKeys);
            }

            $signCheck = hash_hmac('md5', $canonicalRequest, $signingKey, true);

            if ($clientSign !== $signCheck) {
                $errorData = array('debugSign' => array());
                $errorData['debugSign']['canonicalRequest'] = $canonicalRequest;
                $errorData['debugSign']['sessionId'] = $sessionId;
                $errorData['debugSign']['sessionCard'] = $sessionCard;
                $errorData['debugSign']['clientSign'] = $clientSign;
                $errorData['debugSign']['signHeaderKeysStr'] = $headersKeys;
                $errorData['debugSign']['serverSessionKey'] = $data['key'];
                $errorData['debugSign']['signingKey'] = $signingKey;
                $errorData['debugSign']['sessionSignCheck'] = $signCheck;
                throw new AuthErrorException('Signature authentication failure', 'AUTHORIZATION_SIGNATURE_FAILURE', 403, $errorData);
            }
            self::$accessKeyId = $sessionId;
            self::$sessionCard = $sessionCard;
            return true;
        } catch (AuthErrorException $e) {
            if (empty($_GET['redirect_uri'])) {
                throw $e;
            } else {
                @header('Location: ' . $_GET['redirect_uri']);
                die();
            }
        }
    }

    public function getSignUrl($sessionId, $authData, $path = '/', $query = array(), $noSignQuery = array(), $method = 'GET', $headers = array(), $authClassName = null)
    {
        $expiredTimeOffset = 1800;
        $signTime = time();
        $sessionKey = $authData['key'];
        $sessionCard = $authData['card'];
        if (empty($sessionKey)) {
            throw new AuthErrorException('Auth key empty', 'MUST_AUTH_KEY', 400);
        }
        if (empty($sessionCard)) {
            throw new AuthErrorException('Auth card empty', 'MUST_AUTH_CARD', 400);
        }

        $path = '/' . implode('/', array_filter(explode('/', $path)));
        $pathArray = explode('?', $path);
        $path = $pathArray[0];
        if (!empty($pathArray[1])) {
            parse_str($pathArray[1], $pathQuery);
            if (is_array($pathQuery)) {
                $query = array_merge($pathQuery, $query);
            }
        }
        $path = DdvUrl::urlEncodeExceptSlash($path);
        $noSignQuery = empty($noSignQuery) ? array() : $noSignQuery;
        $headersKeys = empty($headers) ? array() : array_keys($headers);

        // 编码
        $query = DdvUrl::buildQuery($query);
        if ($noSignQuery === true) {
            $canonicalQuery = '';
        } else {
            // 重新排序编码
            $canonicalQuery = Sign::canonicalQuerySort($query);

            if ($canonicalQuery) {
                $canonicalQueryArray = explode('&', $canonicalQuery);
                $canonicalQueryArrayNew = array();
                foreach ($canonicalQueryArray as $key => $t) {
                    $ts = explode('=', $t);
                    if (!($ts && $ts[0] && in_array(strtolower($ts[0]), $noSignQuery))) {
                        $canonicalQueryArrayNew[] = $t;
                    }
                }
                $canonicalQuery = implode('&', $canonicalQueryArrayNew);
            }
        }
        $url = $path;
        if ($query) {
            $url .= '?' . $query;
        }
        // 获取签名头
        $canonicalHeaders = Sign::getCanonicalHeaders($headers);

        // 授权字符串
        $authString = "ddv-url-v1/{$sessionId}/{$sessionCard}/{$signTime}/{$expiredTimeOffset}";
        //生成加密key
        $signingKey = hash_hmac('sha256', $authString, $sessionKey);
        //生成需要签名的信息体
        $canonicalRequest = "{$method}\n{$path}\n{$canonicalQuery}\n{$canonicalHeaders}";
        if (empty($noSignQuery)) {
            $canonicalRequest .= "\n";
        } elseif ($noSignQuery === true) {
            $canonicalRequest .= "\ntrue";
        } else {
            $canonicalRequest .= "\n" . implode(',', $noSignQuery);
        }
        if (empty($headersKeys)) {
            $canonicalRequest .= "\n";
        } else {
            $canonicalRequest .= "\n" . implode(',', $headersKeys);
        }
        $signArray = array(
            $sessionId,
            $signTime,
            $expiredTimeOffset,
            $noSignQuery,
            $headersKeys
        );
        //服务端模拟客户端算出的签名信息
        $signJson = gzcompress(json_encode($signArray), 9) . hash_hmac('md5', $canonicalRequest, $signingKey, true);
        $signBase64 = base64_encode($signJson);
        $signBase64 = preg_replace('/\//', '_', preg_replace('/\+/', '-', $signBase64));
        $ddvRestfulApi = \DdvPhp\DdvRestfulApi::getInstance();
        $url .= (strpos($url, '?') === false ? '?' : '&') . $ddvRestfulApi->getHeadersPrefix() . 'auth=ddv-url-v1%2F' . $signBase64;
        return $url;
    }
}
