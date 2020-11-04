<?php

namespace Packlink\Utilities;

use Packlink\Infrastructure\Logger\Logger;

class UpdateScriptsRunner
{
    /** @var \Packlink\Utilities\VersionedFileReader */
    protected $filerReader;

    /**
     * UpdateScriptsRunner constructor.
     *
     * @param \Packlink\Utilities\VersionedFileReader $filerReader
     */
    public function __construct(\Packlink\Utilities\VersionedFileReader $filerReader)
    {
        $this->filerReader = $filerReader;
    }

    /**
     * Executes update scripts.
     *
     * @return bool
     */
    public function run()
    {
        while (($result = $this->filerReader->readNext()) !== null) {
            if ($result === false) {
                Logger::logError('Failed to execute update script.');

                return false;
            }
        }

        return true;
    }
}