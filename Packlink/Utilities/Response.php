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
        header('Cache-Control: max-age=86400');
        $contents = file_get_contents($path);

        echo $contents;

        exit();
    }

    /**
     * Returns file response.
     *
     * @param $filePath
     * @param string $outputFileName
     */
    public static function file($filePath, $outputFileName = '')
    {
        $fileName = $outputFileName !== '' ? $outputFileName : basename($filePath);

        header('Content-Description: File Transfer');
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename=' . $fileName);
        header('Expires: 0');
        header('Cache-Control: must-revalidate');
        header('Pragma: public');
        header('Content-Length: ' . filesize($filePath));
        readfile($filePath);

        die();
    }

    /**
     * Returns inline file.
     *
     * @param string $filePath
     * @param string $type
     * @param string $outputFileName
     */
    public static function inlineFile($filePath, $type, $outputFileName = '')
    {
        $fileName = $outputFileName !== '' ? $outputFileName : basename($filePath);

        header('Content-Type: ' . $type);
        header('Content-Disposition: inline; filename=' . $fileName);
        header('Expires: 0');
        header('Cache-Control: must-revalidate');
        header('Pragma: public');
        header('Content-Length: ' . filesize($filePath));
        readfile($filePath);

        die();
    }

    /**
     * Sets string specified by $content as a file response.
     *
     * @param string $content Content to output as file.
     * @param string $fileName The name of the file.
     */
    public static function fileFromString($content, $fileName)
    {
        header('Content-Description: File Transfer');
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename=' . $fileName);
        header('Expires: 0');
        header('Cache-Control: must-revalidate');
        header('Pragma: public');
        header('Content-Length: ' . strlen($content));

        echo $content;

        die();
    }
}