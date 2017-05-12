<?php 
abstract class AuthDataDriver implements AuthDataHandlerInterface {
  public function __construct($params)
  {
    parent::__construct($params);
  }
  public function open($savePath, $name)
  {
    var_dump($savePath, $name);
  }
  public function read($sessionId)
  {
    var_dump($sessionId);
  }
  public function write($sessionId, $authData)
  {
    var_dump($sessionId);
  }
  public function close()
  {
    var_dump($sessionId);
  }
  public function destroy($sessionId)
  {
    var_dump($sessionId);
  }
  public function gc($maxlifetime)
  {
    var_dump($maxlifetime);
  }
}
?>