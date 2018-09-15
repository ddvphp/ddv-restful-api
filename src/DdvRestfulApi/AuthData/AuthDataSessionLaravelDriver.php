<?php

namespace DdvPhp\DdvRestfulApi\AuthData;

use DdvPhp\DdvRestfulApi\Abstracts\AuthDataDriver as AuthDataDriver;
use DdvPhp\DdvRestfulApi\Interfaces\AuthDataDriver as AuthDataDriverInterface;

class AuthDataSessionLaravelDriver extends AuthDataDriver implements AuthDataDriverInterface
{
    public $session = null;

    public function __construct()
    {
        parent::__construct();
    }

    public function open($authDataDriverConfig = null)
    {
        $this->session = $authDataDriverConfig['session'];
        unset($authDataDriverConfig);
        return $this->_success;
    }

    public function read($sessionId)
    {
        $session = $this->session->getSession($sessionId);
        $session->start();
        $authData = $session->get('__ddvAuthData__');
        return $authData;
    }

    public function write($sessionId, $authData)
    {
        $session = $this->session->getSession($sessionId);
        $session->put('__ddvAuthData__', $authData);
        $session->save();
        return $this->_success;
    }

    public function generateSessionId()
    {
        $session = $this->session->getSession('');
        $session->setId(bin2hex(random_bytes(20)));
        return $session->getId();
    }

    public function close()
    {
        $this->session = null;
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
