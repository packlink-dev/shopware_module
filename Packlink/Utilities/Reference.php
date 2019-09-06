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
        return "https://pro.packlink.$userCountry/private/shipments/$reference";
    }
}