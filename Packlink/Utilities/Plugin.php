<?php

namespace Packlink\Utilities;

class Plugin
{
    /**
     * Retrieves plugin version.
     *
     * @return string
     */
    public static function getVersion()
    {
        $config = simplexml_load_string(file_get_contents(__DIR__ . '/../plugin.xml'));
        $config = json_decode(json_encode($config), true);

        return !empty($config['version']) ? $config['version'] : '';
    }
}