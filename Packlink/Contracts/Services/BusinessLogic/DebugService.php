<?php

namespace Packlink\Contracts\Services\BusinessLogic;

interface DebugService
{
    /**
     * Returns path to zip archive that contains current system info.
     *
     * @return string
     */
    public static function getSystemInfo();
}