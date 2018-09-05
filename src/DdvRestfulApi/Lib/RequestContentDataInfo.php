<?php
/**
 * Created by PhpStorm.
 * User: sicmouse
 * Date: 2018/8/31
 * Time: 下午5:18
 */

namespace DdvPhp\DdvRestfulApi\Lib;

use Closure;

class RequestContentDataInfo
{
    /**
     * @var array
     */
    protected $info = array();
    /**
     * @var array
     */
    protected $data = array();
    /**
     * 毁掉钩子
     * @var array
     */
    protected $hookCompleteds = array();

    public function __set($name, $value)
    {
        if ($name === 'data') {
            $this->data = $value;
        } elseif ($name === 'info') {
            $this->info = $value;
        } else {
            if (!isset($this->info)) {
                $this->info = array();
            }
            $this->info[$name] = $value;
        }
    }

    public function &__get($name)
    {
        $value = null;
        if ($name === 'data') {
            if (empty($this->data)) {
                $this->data = array();
            }
            $value = &$this->data;
        } elseif ($name === 'info') {
            if (empty($this->info)) {
                $this->info = array();
            }
            $value = &$this->info;
        } elseif ($name) {
            if (empty($this->info[$name])) {
                $this->info[$name] = '';
            }
            $value = &$this->info[$name];
        }
        return $value;
    }

    public function __isset($name)
    {
        if ($name === 'data') {
            return true;
        }
        return isset($this->info[$name]);
    }

    public function onCompleted(Closure $fn)
    {
        if (!is_array($this->hookCompleteds)) {
            $this->hookCompleteds = [];
        }
        $this->hookCompleteds[] = $fn;
    }

    public function emitCompleted()
    {
        if (is_array($this->hookCompleteds)) {
            $fns = $this->hookCompleteds;
            foreach ($fns as $fn) {
                if ($fn instanceof Closure) {
                    $fn();
                }
            }
            unset($fns);
        }
        return $this;
    }

    /**
     * 重置
     */
    public function reset($name = null)
    {
        $this->info = array();
        $this->hookCompleteds = array();
        if (!empty($name)) {
            $currLev = $name . '=p';
            $tmp = array();
            parse_str($currLev, $tmp);
            $isRun = true;
            $_this = &$tmp;
            while ($isRun) {
                $isRun = false;
                if (is_array($_this)) {
                    foreach ($_this as $key => $value) {
                        $_this2 = &$_this[$key];
                        unset($_this);
                        $_this = &$_this2;
                        unset($_this2);
                        $isRun = true;
                    }
                }
            }
            if (isset($this->value)) {
                unset($this->value);
            }
            if (is_array($this->info)) {
                if (isset($this->info['value'])) {
                    unset($this->info['value']);
                }
                $_this = '';
                $this->info['value'] = &$_this;
            }
            self::recursiveSetter($name, $this->data, $tmp);
            unset($_this, $isRun, $currLev, $tmp);
        }
        return $this;
    }

    public static function &recursiveSetter($spec, &$array, &$array2)
    {
        if (!is_array($spec)) {
            $spec = explode('[', (string)$spec);
        }
        $currLev = array_shift($spec);
        $currLev = rtrim($currLev, ']');
        if ($currLev !== '') {
            $currLev = $currLev . '=p';
            $tmp = array();
            parse_str($currLev, $tmp);
            $tmp = array_keys($tmp);
            $currLev = reset($tmp);
        }

        if (!is_array($array)) {
            $array = &$array2;
        } else if ($currLev === '') {
            $key = key($array2);
            if ($key && isset($array2[$key])) {
                $array[] = &$array2[$key];
            }
            unset($key);
        } else if (isset($array[$currLev]) && isset($array2[$currLev])) {
            $array[$currLev] = &self::recursiveSetter($spec, $array[$currLev], $array2[$currLev]);
        } else if (isset($array2[$currLev])) {
            $array[$currLev] = &$array2[$currLev];
            //var_dump('xx*x/x*x*x/xx//==',$array2[ $currLev ]);
        }
        return $array;
    }

    public function getData()
    {
        return $this->data;
    }

    public function destroy()
    {
        if (is_array($this->info)) {
            if (isset($this->info['value'])) {
                unset($this->info['value']);
            }
        }
        $this->data = null;
        $this->info = null;
        $this->hookCompleteds = null;
        return $this;
    }
}
