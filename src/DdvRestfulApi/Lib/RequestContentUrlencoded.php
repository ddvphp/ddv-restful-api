<?php
/**
 * Created by PhpStorm.
 * User: sicmouse
 * Date: 2018/8/31
 * Time: 下午2:04
 */

namespace DdvPhp\DdvRestfulApi\Lib;

use Closure;
use DdvPhp\DdvRestfulApi\Abstracts\RequestContentParses;
use DdvPhp\DdvRestfulApi\Interfaces\HttpRequestStream;
use DdvPhp\DdvRestfulApi\Interfaces\RequestContentParses as RequestContentParsesInterfaces;
use DdvPhp\DdvUrl;
use function GuzzleHttp\Psr7\str;

class RequestContentUrlencoded extends RequestContentParses implements RequestContentParsesInterfaces
{
    /**
     * 分隔符
     * @var string
     */
    protected $boundary = '&';
    protected $CRLFBoundaryCRLF = '';
    protected $CRLFBoundaryCRLFLength = 0;
    protected $isWriteBodyBuffer = false;
    protected $isWriteHeaderBuffer = false;
    /**
     * @var RequestContentDataInfo|null
     */
    protected $files = null;
    /**
     * @var RequestContentDataInfo|null
     */
    protected $data = null;
    protected $first = true;
    protected $nowBodyType = null;

    public function __construct(HttpRequestStream $httpRequestStream)
    {
        $this->boundary = '&';
        parent::__construct($httpRequestStream);
        // 重置数据
        $this->reset();
    }

    public function reset()
    {
        // 调用上一层重置
        parent::reset();
        if (!($this->data instanceof RequestContentDataInfo)) {
            $this->data = new RequestContentDataInfo();
        }
        // 重试
        $this->data->reset();
        $this->onCompleted(function () {
            $this->httpRequestStream->setDatas($this->data->emitCompleted()->getData());
            // 重试
            $this->data->reset()->destroy();
        });
    }

    public function write($buffer)
    {
        // 写入内容长度叠加
        $this->writeLength += strlen($buffer);
        // 写入临时缓冲区
        $this->writeTempBuffer .= $buffer;
        unset($buffer);
        // 在写入头临时缓冲区
        if (strpos($this->writeTempBuffer, $this->boundary)) {
            $res = explode($this->boundary, $this->writeTempBuffer);
            if (count($res) > 1) {
                if (!$this->isCompleted()) {
                    $this->writeTempBuffer = array_splice($res, -1)[0];
                }

                foreach ($res as $item) {
                    if (strpos($item, '=') !== false) {
                        $res = explode('=', $item, 2);
                        $this->data->reset($res[0])->value = DdvUrl::urlDecode(trim($res[1]));
                    }
                }
                if (!$this->isCompleted() && strlen($this->writeTempBuffer) > 0) {
                    $this->write('');
                }
            }
        } else {
            $this->data->value .= $this->writeTempBuffer;
        }
        $this->checkCompleted();
    }

    protected function writeBody($buffer)
    {
        if ($this->nowBodyType === 'data') {
            $this->data->value .= $buffer;
        } elseif ($this->nowBodyType === 'file') {
            $this->files->value['size'] += strlen($buffer);
            if ($this->files->fileHandle && fwrite($this->files->fileHandle, $buffer) === false) {
                $this->files->value['error'] = UPLOAD_ERR_CANT_WRITE;
            }
        }
    }

    public function destroy()
    {
        if ($this->data instanceof RequestContentDataInfo) {
            $this->data->reset()->destroy();
        }
    }
}
