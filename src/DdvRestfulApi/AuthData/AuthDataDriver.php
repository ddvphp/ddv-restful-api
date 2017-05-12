<?php 
namespace DdvPhp\DdvRestfulApi\AuthData;
abstract class AuthDataDriver implements \DdvPhp\DdvRestfulApi\AuthData\AuthDataHandlerInterface {

  protected $_config;
  protected $_success, $_failure;
  /**
   * Data fingerprint
   *
   * @var	bool
   */
  protected $_fingerprint;

  /**
   * Lock placeholder
   *
   * @var	mixed
   */
  protected $_lock = FALSE;

  /**
   * Read session ID
   *
   * Used to detect session_regenerate_id() calls because PHP only calls
   * write() after regenerating the ID.
   *
   * @var	string
   */
  protected $_session_id;

  public function __construct()
  {

  }

  protected function _cookie_destroy()
  {
    return setcookie(
        $this->_config['cookie_name'],
        $this->_config['cookie_path'],
        $this->_config['cookie_domain'],
        $this->_config['cookie_secure'],
        TRUE
    );
  }
}
?>