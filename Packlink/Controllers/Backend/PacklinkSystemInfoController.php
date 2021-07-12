<?php

use Packlink\BusinessLogic\Controllers\SystemInfoController;
use Packlink\Utilities\Response;

/**
 * Class Shopware_Controllers_Backend_PacklinkSystemInfo
 */
class Shopware_Controllers_Backend_PacklinkSystemInfoController extends Enlight_Controller_Action
{
    /**
     * @var SystemInfoController
     */
    private $baseController;

    /**
     * Returns a list with actions which should not be validated for CSRF protection
     *
     * @return string[]
     */
    public function getWhitelistedCSRFActions()
    {
        return [
            'get'
        ];
    }

    /**
     * Returns system information.
     */
    public function getAction()
    {
        Response::dtoEntitiesResponse($this->getBaseController()->get());
    }

    /**
     * @return SystemInfoController
     */
    protected function getBaseController()
    {
        if ($this->baseController === null) {
            $this->baseController = new SystemInfoController();
        }

        return $this->baseController;
    }
}