<?php

use Packlink\BusinessLogic\Controllers\DashboardController;
use Packlink\Utilities\Response;
use Shopware\Components\CSRFWhitelistAware;

class Shopware_Controllers_Backend_PacklinkDashboard extends Enlight_Controller_Action implements CSRFWhitelistAware
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

    /**
     * Retrieves setup status.
     */
    public function indexAction()
    {
        $controller = new DashboardController();

        Response::json($controller->getStatus()->toArray());
    }
}