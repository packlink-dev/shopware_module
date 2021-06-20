<?php

use Packlink\BusinessLogic\Configuration;
use Packlink\BusinessLogic\Controllers\WarehouseController;
use Packlink\BusinessLogic\DTO\Exceptions\FrontDtoNotRegisteredException;
use Packlink\BusinessLogic\DTO\Exceptions\FrontDtoValidationException;
use Packlink\Infrastructure\Logger\Logger;
use Packlink\Infrastructure\ServiceRegister;
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
     * @throws FrontDtoNotRegisteredException
     * @throws \Packlink\Infrastructure\TaskExecution\Exceptions\QueueStorageUnavailableException
     */
    public function updateAction()
    {
        $data = Request::getPostData();
        $data['default'] = true;

        /** @var WarehouseService $warehouseService */
        $warehouseService = ServiceRegister::getService(WarehouseService::CLASS_NAME);

        try {
            $warehouse = $warehouseService->updateWarehouseData($data);

            Response::json($warehouse->toArray());
        } catch (FrontDtoValidationException $e) {
            Response::validationErrorsResponse($e->getValidationErrors());
        }
    }

    /**
     * Performs location search.
     */
    public function searchAction()
    {
        $input = Request::getPostData();
        if (empty($input['query']) || empty($input['country'])) {
            Response::json();
        }

        try {
            $result = $this->getLocationService()->searchLocations($input['country'], $input['query']);
        } catch (Exception $e) {
            $result = [];

            Logger::logError("Location search failed because: [{$e->getMessage()}]");
        }

        Response::dtoEntitiesResponse($result);
    }

    /**
     * Returns countries supported by Packlink.
     */
    public function getCountriesAction()
    {
        Configuration::setUICountryCode($this->getLocale());
        $warehouseController = new WarehouseController();

        Response::dtoEntitiesResponse($warehouseController->getWarehouseCountries());
    }

    /**
     * @return string
     */
    protected function getLocale()
    {
        $locale = 'en';

        if ($auth = Shopware()->Container()->get('auth')) {
            $locale = substr($auth->getIdentity()->locale->getLocale(), 0, 2);
        }

        return in_array($locale, ['en', 'de', 'es', 'fr', 'it']) ? $locale : 'en';
    }
}
