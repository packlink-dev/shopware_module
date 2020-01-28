<?php

use Packlink\BusinessLogic\Controllers\DashboardController;
use Packlink\Utilities\Response;

class Shopware_Controllers_Backend_PacklinkDashboard extends Enlight_Controller_Action
{
    /**
     * Retrieves setup status.
     *
     * @throws \Packlink\BusinessLogic\DTO\Exceptions\FrontDtoNotRegisteredException
     */
    public function indexAction()
    {
        $controller = new DashboardController();

        try {
            $status = $controller->getStatus();
            Response::json($status->toArray());
        } catch (\Packlink\BusinessLogic\DTO\Exceptions\FrontDtoValidationException $e) {
            Response::validationErrorsResponse($e->getValidationErrors());
        }
    }
}