<?php

namespace Packlink\Utilities;

class Response
{
    /**
     * Returns json response.
     *
     * @param array $data
     * @param int $status
     */
    public static function json(array $data = [], $status = 200)
    {
        header('Content-Type: application/json');
        http_response_code($status);

        die(json_encode($data));
    }

    /**
     * Returns image response.
     *
     * @param $path
     *
     * @param string $type
     */
    public static function image($path, $type = 'png')
    {
        header("Content-Type: image/$type");
        $contents = file_get_contents($path);

        echo $contents;

        exit();
    }
}