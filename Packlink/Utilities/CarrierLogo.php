<?php

namespace Packlink\Utilities;

class CarrierLogo
{
    /**
     * Retrieves carrier logo.
     *
     * @param $country
     * @param $name
     *
     * @return string
     */
    public static function getLogo($country, $name)
    {
        $image = '/' . $country . '/' . strtolower(str_replace(' ', '-', $name)) . '.png';

        return Url::getFrontUrl('PacklinkImage', 'index') . '?image=' . $image;
    }
}