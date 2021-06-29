<?php

namespace Packlink\Lib;

use RuntimeException;

class Composer
{
    protected static $fromBase = __DIR__ . '/../vendor/packlink/integration-core/src/BusinessLogic/Resources/';
    protected static $brandBase = __DIR__ . '/../vendor/packlink/integration-core/src/Brands/Packlink/Resources/';
    protected static $toBase = __DIR__ . '/../Resources/views/backend/_resources/';


    public static function postUpdate()
    {
        static::mkdir(static::$toBase . 'packlink');

        $map = [
            static::$fromBase . 'js' => static::$toBase . 'packlink/js',
            static::$fromBase . 'css' => static::$toBase . 'packlink/css',
            static::$fromBase . 'countries' => static::$toBase . 'packlink/countries',
            static::$brandBase . 'countries' => static::$toBase . 'packlink/brand/countries',
            static::$fromBase . 'templates' => static::$toBase . 'packlink/templates',
            static::$fromBase . 'images' => static::$toBase . 'packlink/images',
            static::$fromBase . 'LocationPicker' => static::$toBase . 'packlink/location',
        ];

        foreach ($map as $from => $to) {
            self::copyDirectory($from, $to);
        }

        copy(static::$toBase . 'images/carriers/carrier.jpg', static::$toBase . 'packlink/images/carriers/carrier.jpg');
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
        if (!file_exists($destination) && !mkdir($destination, 0777, true) && !is_dir($destination)) {
            throw new RuntimeException(sprintf('Directory "%s" was not created', $destination));
        }
    }
}