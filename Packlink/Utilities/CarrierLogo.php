<?php

namespace Packlink\Utilities;

class CarrierLogo
{
    const DEFAULT_CARRIER = 'carrier.jpg';
    protected static $imgDir = '/Resources/views/backend/_resources/images/carriers/';
    /**
     * @var \Shopware\Components\Theme\PathResolver
     */
    protected static $pathResolver;
    /**
     * @var \Shopware\Models\Shop\Shop
     */
    protected static $defaultShop;

    /**
     * Retrieves carrier logo.
     *
     * @param $country
     * @param $name
     *
     * @return string
     *
     * @throws \Exception
     */
    public static function getLogo($country, $name)
    {
        $pluginDir = Shopware()->Container()->getParameter('packlink.plugin_dir');

        $baseDir = $pluginDir . static::$imgDir;
        $image = $baseDir . $country . '/' . strtolower(str_replace(' ', '-', $name)) . '.png';

        if (!file_exists($image)) {
            $image = $baseDir . self::DEFAULT_CARRIER;
        }

        return static::getPathResolver()->formatPathToUrl($image, static::getDefaultShop());
    }

    /**
     * Retrieves path resolver.
     *
     * @return \Shopware\Components\Theme\PathResolver
     *
     * @throws \Exception
     */
    protected static function getPathResolver()
    {
        if (static::$pathResolver === null) {
            static::$pathResolver = Shopware()->Container()->get('theme_path_resolver');
        }

        return static::$pathResolver;
    }

    /**
     * Retrieves default shop.
     *
     * @return \Shopware\Models\Shop\Shop
     */
    protected static function getDefaultShop()
    {
        if (static::$defaultShop === null) {
            static::$defaultShop = Shop::getDefaultShop();
        }

        return static::$defaultShop;
    }
}