<?php

namespace Packlink\Utilities;

class Response
{
    /**
     * Returns json response.
     *
     * @param array $data
     */
    public static function json(array $data)
    {
        header('Content-Type: application/json');

        die(json_encode($data));
    }
}