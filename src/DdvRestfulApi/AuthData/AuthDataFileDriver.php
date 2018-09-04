<?php

namespace DdvPhp\DdvRestfulApi\AuthData;

use Mockery\CountValidator\Exception;
use DdvPhp\DdvRestfulApi\Abstracts\AuthDataDriver as AuthDataDriver;
use DdvPhp\DdvRestfulApi\Interfaces\AuthDataDriver as AuthDataDriverInterface;

/**
 *
 */
class AuthDataFileDriver extends AuthDataDriver implements AuthDataDriverInterface
{
    protected $_file_handle;
    protected $_file_path;
    protected $_file_new;
    protected $_sid_regexp;
    protected static $func_overload;

    public function __construct()
    {
        parent::__construct();
    }

    public function open($authDataDriverConfig = null)
    {
        $authDataDriverConfig = $this->_file_path = dirname(__FILE__) . '/xxx/';
        if (!is_dir($authDataDriverConfig)) {
            if (!mkdir($authDataDriverConfig, 0700, TRUE)) {
                throw new Exception("Session: Configured save path '" . $authDataDriverConfig . "' is not a directory, doesn't exist or cannot be created.");
            }
        } elseif (!is_writable($authDataDriverConfig)) {
            throw new Exception("Session: Configured save path '" . $authDataDriverConfig . "' is not writable by the PHP process.");
        }
        return $this->_success;
    }

    public function read($sessionId)
    {
        if ($this->_file_handle === NULL) {
            $this->_file_new = !file_exists($this->_file_path . $sessionId);
            if (($this->_file_handle = fopen($this->_file_path . $sessionId, 'c+b')) === FALSE) {
                return $this->_failure;
            }
            //加文件锁,确保不同的用户不会同时读到一个文件
            if (flock($this->_file_handle, LOCK_EX) === FALSE) {
                fclose($this->_file_handle);
                $this->_file_handle = NULL;
                return $this->_failure;
            }
            // Needed by write() to detect session_regenerate_id() calls
            $this->_session_id = $sessionId;
            if ($this->_file_new) {
                chmod($this->_file_path . $sessionId, 0600);
                $this->_fingerprint = md5('');
                return '';
            }
        } elseif ($this->_file_handle === FALSE) {
            return $this->_failure;
        } else {
            if ($this->_session_id === $sessionId) {
                rewind($this->_file_handle);
            } else {
                return $this->close() === $this->_failure ? $this->_failure : $this->read($sessionId);
            }
        }
        $session_data = '';
        for ($read = 0, $length = @filesize($this->_file_path . $sessionId); $read < $length; $read += self::strlen($buffer)) {
            if (($buffer = fread($this->_file_handle, $length - $read)) === FALSE) {
                break;
            }
            $session_data .= $buffer;
        }
        $this->_fingerprint = md5($session_data);
        return $session_data;
    }

    public function write($sessionId, $authData)
    {
        // If the two IDs don't match, we have a session_regenerate_id() call
        // and we need to close the old handle and open a new one
        if ($sessionId !== $this->_session_id && ($this->close() === $this->_failure or $this->read($sessionId) === $this->_failure)) {
            return $this->_failure;
        }
        if (!is_resource($this->_file_handle)) {
            return $this->_failure;
        } elseif ($this->_fingerprint === md5($authData)) {
            return !$this->_file_new && !touch($this->_file_path . $sessionId) ? $this->_failure : $this->_success;
        }
        if (!$this->_file_new) {
            ftruncate($this->_file_handle, 0);
            rewind($this->_file_handle);
        }
        if (($length = strlen($authData)) > 0) {
            for ($written = 0; $written < $length; $written += $result) {
                if (($result = fwrite($this->_file_handle, substr($authData, $written))) === FALSE) {
                    break;
                }
            }
            if (!is_int($result)) {
                $this->_fingerprint = md5(substr($authData, 0, $written));
                return $this->_failure;
            }
        }
        $this->_fingerprint = md5($authData);
        return $this->_success;
    }

    public function close()
    {
        //判断是否为资源类型,如果是释放文件锁
        if (is_resource($this->_file_handle)) {
            flock($this->_file_handle, LOCK_UN);
            fclose($this->_file_handle);
            $this->_file_handle = $this->_file_new = $this->_session_id = NULL;
        }
        return $this->_success;
    }

    public function destroy($sessionId)
    {
        if ($this->close() === $this->_success) {
            if (file_exists($this->_file_path . $sessionId)) {
                #销毁cookies
                $this->_cookie_destroy();
                return unlink($this->_file_path . $sessionId) ? $this->_success : $this->_failure;
            }
            return $this->_success;
        } elseif ($this->_file_path !== NULL) {
            clearstatcache();
            if (file_exists($this->_file_path . $sessionId)) {
                $this->_cookie_destroy();
                return unlink($this->_file_path . $sessionId) ? $this->_success : $this->_failure;
            }
            return $this->_success;
        }
        return $this->_failure;
    }

    public function gc($maxlifetime)
    {
        if (!is_dir($this->_config['save_path']) or ($directory = opendir($this->_config['save_path'])) === FALSE) {
            return $this->_failure;
        }
        $ts = time() - $maxlifetime;
        //判断是否为IP格式
        $pattern = $this->_config['match_ip'] === TRUE ? '[0-9a-f]{32}' : '';
        $pattern = sprintf('#\\A%s' . $pattern . $this->_sid_regexp . '\\z#', preg_quote($this->_config['cookie_name']));
        while (($file = readdir($directory)) !== FALSE) {
            // If the filename doesn't match this pattern, it's either not a session file or is not ours
            if (!preg_match($pattern, $file) or !is_file($this->_config['save_path'] . DIRECTORY_SEPARATOR . $file) or ($mtime = filemtime($this->_config['save_path'] . DIRECTORY_SEPARATOR . $file)) === FALSE or $mtime > $ts) {
                continue;
            }
            unlink($this->_config['save_path'] . DIRECTORY_SEPARATOR . $file);
        }
        closedir($directory);
        return $this->_success;
    }

    /**
     * Byte-safe strlen()
     *
     * @param string $str
     * @return  int
     */
    protected static function strlen($str)
    {
        return self::$func_overload ? mb_strlen($str, '8bit') : strlen($str);
    }
}
