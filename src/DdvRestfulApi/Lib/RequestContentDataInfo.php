<?php
/**
 * Created by PhpStorm.
 * User: sicmouse
 * Date: 2018/8/31
 * Time: 下午5:18
 */

namespace DdvPhp\DdvRestfulApi\Lib;

use Closure;
use DdvPhp\DdvUrl;

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
            if (!isset($this->info[$name])) {
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
        $this->resetValue($name);
        return $this;
    }
    protected function resetValue($key){
        if (empty($key)){
            return;
        }
        $key = preg_replace('/\+/', '%20', (string)$key);
        $key = DdvUrl::urlDecode(trim($key));
        $i = strpos($key, "\x00");
        if ($i!==false){
            $key = substr($key, 0, $i);
        }
        if (empty($key) || $key[0] === '['){
            return;
        }
        $keys = array();
        $postLeftBracketPos = 0;
        for ($i = 0; $i < strlen($key); $i++){
            if ($key[$i] === '[' && !$postLeftBracketPos){
                $postLeftBracketPos = $i + 1;
            }elseif($key[$i] === ']'){
                if ($postLeftBracketPos){
                    if (empty($keys)){
                        $keys[] = substr($key, 0, $postLeftBracketPos - 1);
                    }
                    $keys[] = substr($key, $postLeftBracketPos, $i - $postLeftBracketPos);
                    $postLeftBracketPos = 0;
                    if (isset($key[$i+1])&&$key[$i+1]!=='['){
                        break;
                    }
                }
            }
        }
        unset($postLeftBracketPos);
        if (empty($keys)){
            $keys[] = $key;
        }
        for ($i = 0; $i < strlen($keys[0]); $i++){
            $chr = $keys[0][$i];
            if ($chr === ' ' || $chr === '.' || $chr === '[') {
                $keys[0] = substr($keys[0], 0, $i) + '_' + substr($keys[0], $i + 1);
            }
            if ($chr === '[') {
                break;
            }
        }
        $data = &$this->data;
        for ($i = 0, $keysLen = count($keys); $i < $keysLen; $i++){
            $key = preg_replace('/[\'"]$/', '', preg_replace('/^[\'"]/', '', $keys[$i]));
            $lastData = &$data;
            if (($key !== '' && $key !== ' ') || $i === 0){
                if (!isset($data[$key])){
                    $data[$key] = array();
                }
                $data = &$data[$key];
            }else{
                $ct = -1;
                foreach ($data as $p => $pv){
                    if (is_numeric($p)){
                        $ct = (int)$p + 1;
                    }
                }
                $key = $ct + 1;
                unset($ct);
            }
        }
        $lastData[$key] = &$this->info['value'];
        unset($i, $key, $keys, $data, $lastData, $keysLen);
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
