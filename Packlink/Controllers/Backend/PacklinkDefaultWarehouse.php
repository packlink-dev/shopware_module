<?php

use Logeecom\Infrastructure\Logger\Logger;
use Logeecom\Infrastructure\ServiceRegister;
use Packlink\BusinessLogic\Country\CountryService;
use Packlink\BusinessLogic\Warehouse\WarehouseService;
use Packlink\Controllers\Common\CanInstantiateServices;
use Packlink\Utilities\Request;
use Packlink\Utilities\Response;
use Packlink\Utilities\Translation;

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
     * @throws \Logeecom\Infrastructure\TaskExecution\Exceptions\QueueStorageUnavailableException
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
        /** @var \Packlink\BusinessLogic\Country\CountryService $countryService */
        $countryService = ServiceRegister::getService(CountryService::CLASS_NAME);
        $supportedCountries = $countryService->getSupportedCountries();

        foreach ($supportedCountries as $country) {
            $country->name = Translation::get("configuration/country/{$country->code}");
        }

        Response::dtoEntitiesResponse($supportedCountries);
    }
}
