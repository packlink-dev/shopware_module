<?php

namespace Packlink\Utilities;

class Reference
{
    /**
     * Retrieves reference url.
     *
     * @param $userCountry
     * @param $reference
     *
     * @return string
     */
    public static function getUrl($userCountry, $reference)
    {
        $domain = 'com';

        if (in_array($userCountry, ['ES', 'DE', 'FR', 'IT'], true)) {
            $domain = strtolower($userCountry);
        }

        return "https://pro.packlink.$domain/private/shipments/$reference";
    }
}