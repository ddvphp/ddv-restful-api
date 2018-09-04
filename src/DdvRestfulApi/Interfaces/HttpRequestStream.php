<?php

namespace DdvPhp\DdvRestfulApi\Interfaces;

use \Closure;

interface HttpRequestStream
{

    public function getContentLength();

    public function getMultipartBoundary();

    public function isHeaderInited();

    public function checkHeaderInited();

    public function write($buffer);

    public function reset();

    public function setDatas($data);

    public function setFiles($files);

    /**
     * @param $remoteAddress
     * @param $remotePort
     * @param array $info
     * @return $this
     */
    public function setRemoteInfo($remoteAddress, $remotePort, $info = array());

    public function onCompleted(Closure $fn);

    public function onRequested(Closure $fn);

    public function destroy();

    public function baseInit ($headers = array(), $server = array());
}
