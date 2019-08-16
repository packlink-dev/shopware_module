<?php

use Packlink\Utilities\Response;
use Shopware\Components\CSRFWhitelistAware;

class Shopware_Controllers_Frontend_PacklinkImage extends Enlight_Controller_Action implements CSRFWhitelistAware
{
    const DEFAULT_CARRIER = 'carrier.jpg';

    /**
     * Returns a list with actions which should not be validated for CSRF protection
     *
     * @return string[]
     */
    public function getWhitelistedCSRFActions()
    {
        return ['index'];
    }

    /**
     * Retrieves image.
     */
    public function indexAction()
    {
        $type = 'png';

        $image = $this->Request()->get('image');

        $basePath = $this->getBasePath();
        $path = $basePath . $image;

        if (!file_exists($path)) {
            $path = $basePath . self::DEFAULT_CARRIER;
            $type = 'jpeg';
        }

        Response::image($path, $type);
    }

    protected function getBasePath()
    {
        return __DIR__ . '/../../Resources/views/backend/_resources/images/carriers/';
    }
}