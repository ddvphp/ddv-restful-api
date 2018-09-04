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
use function GuzzleHttp\Psr7\str;

class RequestContentMultipart extends RequestContentParses implements RequestContentParsesInterfaces
{
    /**
     * 分隔符
     * @var string
     */
    protected $boundary = '';
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
        parent::__construct($httpRequestStream);
        // 重置数据
        $this->reset();
    }

    public function reset()
    {
        $this->first = true;
        $this->nowBodyType = null;
        // 默认没有在写临时请求体缓冲区
        $this->isWriteBodyBuffer = false;
        // 默认没有在写临时请求头缓冲区
        $this->isWriteHeaderBuffer = false;
        // 调用上一层重置
        parent::reset();
        if (!($this->files instanceof RequestContentDataInfo)) {
            $this->files = new RequestContentDataInfo();
        }
        if (!($this->data instanceof RequestContentDataInfo)) {
            $this->data = new RequestContentDataInfo();
        }
        // 重试
        $this->data->reset();
        $this->files->reset();
        $this->onCompleted(function () {
            $this->httpRequestStream->setDatas($this->data->emitCompleted()->getData());
            $this->httpRequestStream->setFiles($this->files->emitCompleted()->getData());
            // 重试
            $this->data->reset()->destroy();
            $this->files->reset()->destroy();
        });
        // // 分隔符
        $this->boundary = $this->httpRequestStream->getMultipartBoundary();
        if (empty($this->boundary)) {
            // 拼接分隔符和回车
            $this->CRLFBoundaryCRLF = '';
            // 计算分隔符和回车的长度
            $this->CRLFBoundaryCRLFLength = 0;
        } else {
            // 拼接分隔符和回车
            $this->CRLFBoundaryCRLF = $this->CRLF . $this->boundary . $this->CRLF;
            // 计算分隔符和回车的长度
            $this->CRLFBoundaryCRLFLength = strlen($this->CRLFBoundaryCRLF);
        }
    }

    public function write($buffer)
    {
        if ($this->first === true) {
            $this->first = false;
            $this->writeTempBuffer .= $this->CRLF;
        }
        // 写入内容长度叠加
        $this->writeLength += strlen($buffer);
        // 写入临时缓冲区
        $this->writeTempBuffer .= $buffer;
        unset($buffer);
        // 在写入头临时缓冲区
        if ($this->isWriteHeaderBuffer) {
            if (strpos($this->writeTempBuffer, $this->CRLF . $this->CRLF)) {
                list($headerBuffer, $this->writeTempBuffer) = explode($this->CRLF . $this->CRLF, $this->writeTempBuffer, 2);
                $this->isWriteHeaderBuffer = false;
                // 重试
                $this->data->emitCompleted()->reset();
                $this->files->emitCompleted()->reset();
                if (!empty($headerBuffer)) {
                    foreach (explode($this->CRLF, $headerBuffer) as $item) {
                        if (empty($item)) {
                            continue;
                        }
                        list($headerKey, $headerValue) = explode(": ", $item);
                        $tempHeaders[strtolower($headerKey)] = $headerValue;
                    }
                    $this->writeBodyInit($tempHeaders);
                }
                $this->write('');
            }
        } else {
            // 默认没有找到分隔符
            $isFindBoundary = false;
            $buffer = '';
            if ($this->CRLFBoundaryCRLFLength > 0) {
                // 有分隔符可以直接快速匹配
                if (strpos($this->writeTempBuffer, $this->boundary . $this->CRLF)) {
                    // 相当于清理了缓冲区的分隔符内容
                    list($buffer, $this->writeTempBuffer) = explode($this->CRLFBoundaryCRLF, $this->writeTempBuffer, 2);
                    // 找到了分隔符
                    $isFindBoundary = true;
                }
            } else {
                // 没有分隔符的时候直接通过回车查找第一行的就是分隔符
                if ($this->writeLength > strlen($this->CRLF) && $index = strpos($this->writeTempBuffer, $this->CRLF, strlen($this->CRLF))) {
                    // 缓存分隔符，方便后期快速匹配，同时相当于清理了缓冲区的分隔符内容
                    foreach (explode($this->CRLF, substr($this->writeTempBuffer, 0, $index)) as $item) {
                        if (!empty($item)) {
                            $this->boundary = $item;
                        }
                        unset($item);
                    }
                    $this->writeTempBuffer = substr($this->writeTempBuffer, $index + strlen($this->CRLF));
                    // 拼接分隔符和回车
                    $this->CRLFBoundaryCRLF = $this->CRLF . $this->boundary . $this->CRLF;
                    // 计算分隔符和回车的长度
                    $this->CRLFBoundaryCRLFLength = strlen($this->CRLFBoundaryCRLF);
                    // 找到了分隔符
                    $isFindBoundary = true;
                }
                unset($index);
            }
            // 是否遇到分隔符
            if ($isFindBoundary) {
                if ($this->isWriteBodyBuffer) {
                    // 上一个请求体的残余数据
                    $this->writeBody($buffer);
                }
                // 进入 缓冲区 查找头 模式
                $this->isWriteHeaderBuffer = true;
                // 传入空内容，触发下一次操作，好处理缓冲区内的内容
                $this->write('');
            } else {
                if ($this->CRLFBoundaryCRLFLength > 0 && strlen($this->writeTempBuffer) >= $this->CRLFBoundaryCRLFLength) {
                    $buffer = substr($this->writeTempBuffer, 0, -strlen($this->boundary . $this->CRLF));
                    $this->writeTempBuffer = substr($this->writeTempBuffer, -strlen($this->boundary . $this->CRLF));
                    $this->writeBody($buffer);
                }
            }
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

    protected function writeBodyInit($headers)
    {
        $disposition = empty($headers['content-disposition']) ? '' : $headers['content-disposition'];
        // Is file data.
        if (preg_match('/name="(.*?)"; filename="(.*?)"$/', $disposition, $match)) {
            $this->files->reset($match[1]);
            // 文件模式
            $this->nowBodyType = 'file';
            // Parse $_FILES.
            $this->files->name = $match[1];
            // 在完成的时候
            $this->files->onCompleted(function () {
                if ($this->files->fileHandle) {
                    fclose($this->files->fileHandle);
                }
            });
            $this->files->value = array(
                'name' => $match[2],
                'tmp_name' => tempnam(ini_get('upload_tmp_dir'), 'php'),
                'error' => UPLOAD_ERR_OK,
                'size' => 0
            );
            //写流打开文件
            $this->files->fileHandle = fopen($this->files->value['tmp_name'], 'wb');
            // 如果打开失败
            if ($this->files->fileHandle === false) {
                $this->files->value['error'] = UPLOAD_ERR_CANT_WRITE;
            }
            if (!empty($headers['content-type'])) {
                $this->files->value['type'] = $headers['content-type'];
            }
            $this->isWriteBodyBuffer = true;
        } elseif (preg_match('/name="(.*?)"$/', $disposition, $match)) {
            $this->data->reset($match[1]);
            // 数据模式
            $this->nowBodyType = 'data';
            // Is post field.Parse $_POST.
            $this->data->name = $match[1];

            $this->data->onCompleted(function () {
                $encoding = $this->data->encoding;
                if (!empty($encoding) && strtoupper($encoding) !== 'UTF-8' && strtoupper($encoding) !== 'UTF8') {
                    $tmp = mb_convert_encoding($this->data->value, 'UTF-8', $encoding);
                    if ($tmp !== false)
                        $this->data->value = $tmp;
                }
            });
            $this->isWriteBodyBuffer = true;
            if (!empty($headers['content-type'])) {
                $tmp = explode(';', $headers['content-type']);
                $this->data->encoding = '';
                foreach ($tmp as $t) {
                    if (strpos($t, 'charset') !== false) {
                        $t = explode($t, '=', 2);
                        if (isset($t[1]))
                            $this->data->encoding = $t[1];
                        break;
                    }
                }
            }
        }
    }

    public function destroy()
    {
        if ($this->files instanceof RequestContentDataInfo) {
            $this->files->reset()->destroy();
        }
        if ($this->data instanceof RequestContentDataInfo) {
            $this->data->reset()->destroy();
        }
        $this->hookCompleteds = null;
    }
}
