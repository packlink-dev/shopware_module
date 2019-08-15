<?php

namespace Packlink\Utilities;

class Translation
{
    /**
     * @param $key
     *
     * @return string
     */
    public static function get($key)
    {
        return Shopware()->Snippets()->getNamespace('backend/packlink/configuration')->get($key, $key);
    }
}