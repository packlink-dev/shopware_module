<?php

namespace Packlink\Utilities;

/**
 * Class VersionedFileReader
 *
 * @package Packlink\Utilities
 */
class VersionedFileReader
{
    const MIGRATION_FILE_PREFIX = 'migration.v.';

    private $migrationsDirectory;
    private $version;
    private $sortedFilesForExecution = [];
    private $pointer = 0;

    public function __construct($migrationDirectory, $version)
    {
        $this->migrationsDirectory = $migrationDirectory;
        $this->version = $version;
    }

    /**
     * Read next file from list of files for execution
     *
     * @return mixed|null
     */
    public function readNext()
    {
        $fileContent = null;
        if (empty($this->sortedFilesForExecution)) {
            $this->sortFiles();
        }

        if (isset($this->sortedFilesForExecution[$this->pointer])) {
            $fileContent = include($this->migrationsDirectory . $this->sortedFilesForExecution[$this->pointer]);
            $this->pointer++;
        }

        return $fileContent;
    }

    /**
     * Sort and filter files for execution
     */
    private function sortFiles()
    {
        $files = array_diff(scandir($this->migrationsDirectory), array('.', '..'));
        if ($files) {
            $self = $this;
            usort(
                $files,
                function ($file1, $file2) use ($self) {
                    $file1Version = $self->getFileVersion($file1);
                    $file2Version = $self->getFileVersion($file2);

                    return version_compare($file1Version, $file2Version);
                }
            );

            foreach ($files as $file) {
                $fileVersion = $this->getFileVersion($file);
                if (version_compare($this->version, $fileVersion, '<')) {
                    $this->sortedFilesForExecution[] = $file;
                }
            }
        }
    }

    /**
     * Get file version based on file name
     *
     * @param string $file
     *
     * @return string
     */
    private function getFileVersion($file)
    {
        return str_ireplace(array(self::MIGRATION_FILE_PREFIX, '.php'), '', $file);
    }
}