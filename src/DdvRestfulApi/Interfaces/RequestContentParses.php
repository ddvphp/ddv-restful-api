<?php

namespace DdvPhp\DdvRestfulApi\Interfaces;

use \Closure;

interface RequestContentParses
{
    public function __construct(HttpRequestStream $httpRequestStream);

    public function write($buffer);

    public function onCompleted(Closure $fn);

    public function destroy();
}
