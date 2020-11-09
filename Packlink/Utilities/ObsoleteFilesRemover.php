<?php

namespace Packlink\Utilities;

use Exception;
use Packlink\Infrastructure\Logger\Logger;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

class ObsoleteFilesRemover
{
    /**
     * @var VersionedFileReader
     */
    private $versionFileReader;
    /** @var string */
    private $pluginPath;

    /**
     * ObsoleteFilesRemover constructor.
     *
     * @param $pluginPath
     * @param $versionFileReader
     */
    public function __construct($pluginPath, $versionFileReader)
    {
        $this->versionFileReader = $versionFileReader;
        $this->pluginPath = $pluginPath;
    }

    /**
     * Removes files or directories with all its content
     *
     * @return bool
     */
    public function remove()
    {
        while (($fileNames = $this->versionFileReader->readNext()) !== null) {
            foreach ($fileNames as $fileName) {
                try {
                    $file = $this->pluginPath . $fileName;
                    if (is_dir($file)) {
                        $this->removeDirectory($file);
                    } else {
                        unlink($file);
                    }
                } catch (Exception $ex) {
                    // If execution of removing files fails, log error message, return false and break execution
                    Logger::logError($ex->getMessage());

                    return false;
                }
            }
        }

        return true;
    }

    /**
     * Remove directory and all its files and folders
     *
     * @param $directory
     */
    private function removeDirectory($directory)
    {
        $iterator = new RecursiveDirectoryIterator($directory, RecursiveDirectoryIterator::SKIP_DOTS);
        $files = new RecursiveIteratorIterator($iterator, RecursiveIteratorIterator::CHILD_FIRST);

        foreach ($files as $file) {
            if ($file->isDir()) {
                rmdir($file->getRealPath());
            } else {
                unlink($file->getRealPath());
            }
        }

        rmdir($directory);
    }
}