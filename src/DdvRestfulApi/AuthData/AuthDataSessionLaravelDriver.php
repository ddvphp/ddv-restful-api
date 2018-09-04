<?php

namespace DdvPhp\DdvRestfulApi\AuthData;

use DdvPhp\DdvRestfulApi\Abstracts\AuthDataDriver as AuthDataDriver;
use DdvPhp\DdvRestfulApi\Interfaces\AuthDataDriver as AuthDataDriverInterface;

class AuthDataSessionLaravelDriver extends AuthDataDriver implements AuthDataDriverInterface
{
    public $request = null;
    public $session = null;
    public $startSession = null;
    public $sessionName = null;

    public function __construct()
    {
        parent::__construct();
    }

    public function open($authDataDriverConfig = null)
    {
        $this->request = $authDataDriverConfig['request'];
        $this->startSession = $authDataDriverConfig['session'];
        $this->sessionName = $authDataDriverConfig['sessionName'];
        return $this->_success;
    }

    public function read($sessionId)
    {
        $sessionId = strlen($sessionId) === 40 ? $sessionId : $sessionId . '88888888';
        try {
            // 直接通过cookie 数组 重写 $sessionId
            $_COOKIE[$this->sessionName] = $sessionId;
        } catch (\Exception $e) {
        }
        try {
            // 通过请求对象来重写 $sessionId
            $this->request->cookies->set($this->sessionName, $sessionId);
        } catch (\Exception $e) {
        }
        $session = $this->startSession->getSession($this->request);
        $session->setId($sessionId);
        $session->start();
        $authData = $session->get('__ddvAuthData__');
        return $authData;
    }

    public function write($sessionId, $authData)
    {
        $sessionId = strlen($sessionId) === 40 ? $sessionId : $sessionId . '88888888';
        try {
            // 直接通过cookie 数组 重写 $sessionId
            $_COOKIE[$this->sessionName] = $sessionId;
        } catch (\Exception $e) {
        }
        try {
            // 通过请求对象来重写 $sessionId
            $this->request->cookies->set($this->sessionName, $sessionId);
        } catch (\Exception $e) {
        }
        $session = $this->startSession->getSession($this->request);
        $session->setId($sessionId);
        $session->put('__ddvAuthData__', $authData);
        $session->save();
        return $this->_success;
    }

    public function generateSessionId()
    {
        $session = $this->startSession->getSession($this->request);
        $session->setId(bin2hex(random_bytes(20)));
        return $session->getId();
    }

    public function close()
    {
        $this->request = null;
        $this->startSession = null;
        $this->sessionName = null;
        return $this->_success;
    }

    public function destroy($sessionId)
    {
        return $this->_success;
    }

    public function gc($maxlifetime)
    {
        return $this->_success;
    }
}
