<?php 
  namespace DdvPhp\DdvRestfulApi\AuthData;

interface AuthDataHandlerInterface {
  public function open($savePath, $name);
  public function close();
  public function read($sessionId);
  public function write($sessionId, $authData);
  public function destroy($sessionId);
  public function gc($maxlifetime);
}

?>