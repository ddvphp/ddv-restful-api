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

    if (is_php('7'))
    {
      $this->_success = TRUE;
      $this->_failure = FALSE;
    }
    else
    {
      $this->_success = 0;
      $this->_failure = -1;
    }
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

  /**
   * Get lock
   *
   * A dummy method allowing drivers with no locking functionality
   * (databases other than PostgreSQL and MySQL) to act as if they
   * do acquire a lock.
   *
   * @param	string	$session_id
   * @return	bool
   */
  protected function _get_lock($session_id)
  {
    $this->_lock = TRUE;
    return TRUE;
  }

  // ------------------------------------------------------------------------

  /**
   * Release lock
   *
   * @return	bool
   */
  protected function _release_lock()
  {
    if ($this->_lock)
    {
      $this->_lock = FALSE;
    }

    return TRUE;
  }

  // ------------------------------------------------------------------------

  /**
   * Fail
   *
   * Drivers other than the 'files' one don't (need to) use the
   * session.save_path INI setting, but that leads to confusing
   * error messages emitted by PHP when open() or write() fail,
   * as the message contains session.save_path ...
   * To work around the problem, the drivers will call this method
   * so that the INI is set just in time for the error message to
   * be properly generated.
   *
   * @return	mixed
   */
  protected function _fail()
  {
    ini_set('session.save_path', config_item('sess_save_path'));
    return $this->_failure;
  }

}
?>