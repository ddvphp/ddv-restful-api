<?php

namespace DdvPhp\DdvRestfulApi\Interfaces;

interface AuthDataDriver
{
    public function open($authDataDriverConfig);

    public function close();

    public function read($sessionId);

    public function write($sessionId, $authData);

    public function destroy($sessionId);

    public function gc($maxlifetime);
}
