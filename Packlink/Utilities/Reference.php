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
        $userCountry = strtolower($userCountry);

        if (in_array($userCountry, ['es', 'de', 'fr', 'it'], true)) {
            return "https://pro.packlink.$userCountry/private/shipments/$reference";
        }

        return "https://pro.packlink.com/private/shipments/$reference";
    }
}