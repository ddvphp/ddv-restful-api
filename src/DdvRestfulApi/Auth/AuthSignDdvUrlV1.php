<?php 
namespace DdvPhp\DdvRestfulApi\Auth;
use \DdvPhp\DdvRestfulApi\Exception\AuthError as AuthErrorException;
/**
* 
*/
class AuthSignDdvUrlV1 extends AuthAbstract
{
  private $regAuth = 
    '/^([\da-f]{8}-[\da-f]{4}-[\da-f]{4}-[\da-f]{4}-[\da-f]{12})\/([0-9a-zA-Z,-]+)\/([\da-f]{4}-[\da-f]{8}-[\da-f]{4}-[\da-f]{4}-[\da-f]{4}-[\da-f]{12}-[\da-f]{8})\/([\d]{4}-[\d]{2}-[\d]{2}T[\d]{2}:[\d]{2}:[\d]{2}Z)\/(\d+)\/([\w\-\;]+)\/([\da-f]{64})$/i';
  protected function sign()
  {
    // 试图旧授权信息
    $this->checkAuth();
  }
  private function checkAuth()
  {
    var_dump($this->authorization);die;
  }
  public static function getSignUrl($authData, $path = '/', $query = array(), $noSignQuery = array(), $method = 'GET', $headers = array(), $authClassName = null)
  {
    var_dump($authData);
  }
}
 ?>