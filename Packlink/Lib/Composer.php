<?php

namespace Packlink\Lib;

use RuntimeException;

class Composer
{
    protected static $formBase = __DIR__ . '/../vendor/packlink/integration-core/src/BusinessLogic/Resources/';
    protected static $toBase = __DIR__ . '/../Resources/views/backend/_resources/';


    public static function postUpdate()
    {
        $map = [
            static::$formBase . 'js' => static::$toBase . 'js',
            static::$formBase . 'img/carriers/de' => static::$toBase . 'images/carriers/de',
            static::$formBase . 'img/carriers/fr' => static::$toBase . 'images/carriers/fr',
            static::$formBase . 'img/carriers/it' => static::$toBase . 'images/carriers/it',
            static::$formBase . 'img/carriers/es' => static::$toBase . 'images/carriers/es',
        ];

        foreach ($map as $from => $to) {
            self::copyDirectory($from, $to);
        }
    }

    /**
     * Copies directory.
     *
     * @param string $src
     * @param string $dst
     */
    private static function copyDirectory($src, $dst)
    {
        $dir = opendir($src);
        self::mkdir($dst);

        while (false !== ($file = readdir($dir))) {
            if (($file !== '.') && ($file !== '..')) {
                if (is_dir($src . '/' . $file)) {
                    self::mkdir($dst . '/' . $file);

                    self::copyDirectory($src . '/' . $file, $dst . '/' . $file);
                } else {
                    copy($src . '/' . $file, $dst . '/' . $file);
                }
            }
        }

        closedir($dir);
    }

    /**
     * Creates directory.
     *
     * @param string $destination
     */
    private static function mkdir($destination)
    {
        if (!file_exists($destination) && !mkdir($destination) && !is_dir($destination)) {
            throw new RuntimeException(sprintf('Directory "%s" was not created', $destination));
        }
    }
}