<?php

use Shopware\Components\CSRFWhitelistAware;

class Shopware_Controllers_Backend_PacklinkLogin extends Enlight_Controller_Action implements CSRFWhitelistAware
{
    /**
     * Returns a list with actions which should not be validated for CSRF protection
     *
     * @return string[]
     */
    public function getWhitelistedCSRFActions()
    {
        return ['index'];
    }

    public function indexAction()
    {
    }
}