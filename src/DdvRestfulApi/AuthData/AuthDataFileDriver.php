<?php 
namespace DdvPhp\DdvRestfulApi\AuthData;
/**
* 
*/
class AuthDataFileDriver
extends \DdvPhp\DdvRestfulApi\AuthData\AuthDataDriver
implements \DdvPhp\DdvRestfulApi\AuthData\AuthDataHandlerInterface
{
  
  public function __construct()
  {
    parent::__construct();
  }
  public function open($authDataDriverConfig = null)
  {
    // var_dump($authDataDriverConfig);
  }
  public function read($sessionId)
  {
    return 'a:2:{s:4:"card";s:50:"ed9a-d251b2e6-48c3-9c08-e426-ed15398ac305-73624bb2";s:3:"key";s:100:"c4ba-ae8878c1641b-270a-073bb98e-cc54-1590-2a48-79a304e5a6cb-9dda07f2-1d03eef14b56-29d0-5a14db07-abf6";}';
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