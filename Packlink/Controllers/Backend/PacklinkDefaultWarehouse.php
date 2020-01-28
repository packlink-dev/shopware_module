<?php

use Logeecom\Infrastructure\Logger\Logger;
use Logeecom\Infrastructure\ServiceRegister;
use Packlink\BusinessLogic\Warehouse\WarehouseService;
use Packlink\Controllers\Common\CanInstantiateServices;
use Packlink\Utilities\Request;
use Packlink\Utilities\Response;

class Shopware_Controllers_Backend_PacklinkDefaultWarehouse extends Enlight_Controller_Action
{
    use CanInstantiateServices;

    /**
     * Retrieves default warehouse.
     */
    public function indexAction()
    {
        /** @var WarehouseService $warehouseService */
        $warehouseService = ServiceRegister::getService(WarehouseService::CLASS_NAME);

        $warehouse = $warehouseService->getWarehouse();

        Response::json($warehouse->toArray());
    }

    /**
     * Updates default warehouse.
     *
     * @throws \Packlink\BusinessLogic\DTO\Exceptions\FrontDtoNotRegisteredException
     */
    public function updateAction()
    {
        $data = Request::getPostData();
        $data['default'] = true;

        /** @var WarehouseService $warehouseService */
        $warehouseService = ServiceRegister::getService(WarehouseService::CLASS_NAME);

        try {
            $warehouseService->setWarehouse($data);
        } catch (\Packlink\BusinessLogic\DTO\Exceptions\FrontDtoValidationException $e) {
            Response::validationErrorsResponse($e->getValidationErrors());
        }

        Response::json($data);
    }

    /**
     * Performs location search.
     */
    public function searchAction()
    {
        $input = Request::getPostData();
        if (empty($input['query'])) {
            Response::json();
        }

        $country = $this->getConfigService()->getUserInfo()->country;

        try {
            $result = $this->getLocationService()->searchLocations($country, $input['query']);
        } catch (Exception $e) {
            $result = [];

            Logger::logError("Location search failed because: [{$e->getMessage()}]");
        }

        $arrayResult = [];
        foreach ($result as $item) {
            $arrayResult[] = $item->toArray();
        }

        Response::json($arrayResult);
    }
}
