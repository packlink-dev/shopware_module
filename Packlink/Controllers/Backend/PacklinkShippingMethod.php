<?php

use Packlink\BusinessLogic\DTO\Exceptions\FrontDtoValidationException;
use Packlink\BusinessLogic\Tax\TaxClass;
use Packlink\Infrastructure\Exceptions\BaseException;
use Packlink\Infrastructure\TaskExecution\QueueItem;
use Packlink\BusinessLogic\Controllers\DTO\ShippingMethodConfiguration;
use Packlink\BusinessLogic\Controllers\ShippingMethodController;
use Packlink\BusinessLogic\Controllers\UpdateShippingServicesTaskStatusController;
use Packlink\Controllers\Common\CanInstantiateServices;
use Packlink\Utilities\Request;
use Packlink\Utilities\Response;
use Packlink\Utilities\Translation;
use Shopware\Models\Tax\Tax;

/**
 * Class Shopware_Controllers_Backend_PacklinkShippingMethod
 */
class Shopware_Controllers_Backend_PacklinkShippingMethod extends Enlight_Controller_Action
{
    use CanInstantiateServices;

    /**
     * @var ShippingMethodController
     */
    private $baseController;

    public function __construct()
    {
        parent::__construct();

        $this->baseController = new ShippingMethodController();
    }

    /**
     * Returns all available shipping methods.
     */
    public function getAllAction()
    {
        $shippingMethods = $this->baseController->getAll();

        Response::dtoEntitiesResponse($shippingMethods);
    }

    /**
     * Returns active shipping methods.
     */
    public function getActiveAction()
    {
        $shippingMethods = $this->baseController->getActive();

        Response::dtoEntitiesResponse($shippingMethods);
    }

    /**
     * Returns inactive shipping methods.
     */
    public function getInactiveAction()
    {
        $shippingMethods = $this->baseController->getInactive();

        Response::dtoEntitiesResponse($shippingMethods);
    }

    /**
     * Returns a single shipping method identified by the provided ID.
     */
    public function getShippingMethodAction()
    {
        $id = $this->request->getQuery('id');

        if ($id === null) {
            Response::json(['success' => false], 400);
        }

        $shippingMethod = $this->baseController->getShippingMethod($id);

        if ($shippingMethod === null) {
            Response::json(['success' => false], 404);
        }

        Response::json($shippingMethod->toArray());
    }

    /**
     * Gets the status of the task for updating shipping services.
     */
    public function getTaskStatusAction()
    {
        $status = QueueItem::FAILED;
        try {
            $controller = new UpdateShippingServicesTaskStatusController();
            $status = $controller->getLastTaskStatus();
        } catch (BaseException $e) {
        }

        Response::json(['status' => $status]);
    }

    /**
     * Activates shipping method.
     */
    public function activateAction()
    {
        $data = Request::getPostData();

        if (!$data['id'] || !$this->baseController->activate((int)$data['id'])) {
            Response::json(['success' => false, 'message' => Translation::get('error/shippingmethodactivate')], 400);
        }

        Response::json(['success' => true, 'message' => Translation::get('success/shippingmethodactivate')]);
    }

    /**
     * Deactivates shipping method.
     */
    public function deactivateAction()
    {
        $data = Request::getPostData();

        if (!$data['id'] || !$this->baseController->deactivate((int)$data['id'])) {
            Response::json(['success' => false, 'message' => Translation::get('error/shippingmethoddeactivate')], 400);
        }

        Response::json(['success' => true, 'message' => Translation::get('success/shippingmethoddeactivate')]);
    }

    /**
     * Saves shipping method.
     */
    public function saveAction()
    {
        try {
            $configuration = $this->getShippingMethodConfiguration();
        } catch (FrontDtoValidationException $e) {
            Response::validationErrorsResponse($e->getValidationErrors());
        }

        $model = $this->baseController->save($configuration);

        if ($model === null) {
            Response::json(['message' => Translation::get('error/shippingmethodsave')], 400);
        }

        if (!$model->id || !$this->baseController->activate((int)$model->id)) {
            Response::json(['message' => Translation::get('error/shippingmethodactivate')], 400);
        }

        Response::json($model->toArray());
    }

    /**
     * Retrieves available taxes.
     */
    public function getTaxClassesAction()
    {
        $result = [];

        try {
            $result[] = TaxClass::fromArray(
                [
                    'value' => 0,
                    'label' => Translation::get('configuration/defaulttax'),
                ]
            );

            $availableTaxes = $this->getTaxRepository()->queryAll()->execute();

            /** @var Tax $tax */
            foreach ($availableTaxes as $tax) {
                $result[] = TaxClass::fromArray([
                    'value' => $tax->getId(),
                    'label' => $tax->getName(),
                ]);
            }

            Response::dtoEntitiesResponse($result);
        } catch (FrontDtoValidationException $e) {
            Response::validationErrorsResponse($e->getValidationErrors());
        }
    }

    /**
     * Retrieves tax repository.
     *
     * @return \Shopware\Models\Tax\Repository
     */
    protected function getTaxRepository()
    {
        /** @noinspection PhpIncompatibleReturnTypeInspection */
        return Shopware()->Models()->getRepository(Tax::class);
    }

    /**
     * Returns shipping method configuration.
     *
     * @return ShippingMethodConfiguration
     *
     * @throws FrontDtoValidationException
     */
    protected function getShippingMethodConfiguration()
    {
        $data = Request::getPostData();

        $data['taxClass'] = (int)$data['taxClass'];

        return ShippingMethodConfiguration::fromArray($data);
    }
}
