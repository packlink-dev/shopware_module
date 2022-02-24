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

	/**
	 * Returns all available shipping methods.
	 */
	public function getAllAction()
	{
		$shippingMethods = $this->getBaseController()->getAll();

        $this->View()->assign('response', Response::dtoEntitiesResponse($shippingMethods));
	}

	/**
	 * Returns active shipping methods.
	 */
	public function getActiveAction()
	{
		$shippingMethods = $this->getBaseController()->getActive();

        $this->View()->assign('response', Response::dtoEntitiesResponse($shippingMethods));
	}

	/**
	 * Returns inactive shipping methods.
	 */
	public function getInactiveAction()
	{
		$shippingMethods = $this->getBaseController()->getInactive();

        $this->View()->assign('response', Response::dtoEntitiesResponse($shippingMethods));
	}

	/**
	 * Returns a single shipping method identified by the provided ID.
	 */
	public function getShippingMethodAction()
	{
		$id = $this->request->getQuery('id');

		if ($id === null) {
		    $this->return400(['success' => false]);

            return;
		}

		$shippingMethod = $this->getBaseController()->getShippingMethod($id);

		if ($shippingMethod === null) {
		    $this->Response()->setStatusCode(404);
            $this->View()->assign('response', ['success' => false]);

            return;
		}

		$this->View()->assign(['response' => $shippingMethod->toArray()]);
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

        $this->View()->assign('response', ['status' => $status]);
	}

	/**
	 * Activates shipping method.
	 */
	public function activateAction()
	{
		$data = Request::getPostData();

		if (!$data['id'] || !$this->getBaseController()->activate((int)$data['id'])) {
		    $this->return400(['success' => false, 'message' => Translation::get('error/shippingmethodactivate')]);

            return;
		}

        $this->View()->assign('response', ['success' => true, 'message' => Translation::get('success/shippingmethodactivate')]);
	}

	/**
	 * Deactivates shipping method.
	 */
	public function deactivateAction()
	{
		$data = Request::getPostData();

		if (!$data['id'] || !$this->getBaseController()->deactivate((int)$data['id'])) {
		    $this->return400(['success' => false, 'message' => Translation::get('error/shippingmethoddeactivate')]);

            return;
		}

        $this->View()->assign('response', ['success' => true, 'message' => Translation::get('success/shippingmethoddeactivate')]);
	}

	/**
	 * Saves shipping method.
	 */
	public function saveAction()
	{
		try {
			$configuration = $this->getShippingMethodConfiguration();
		} catch (FrontDtoValidationException $e) {
            $this->View()->assign('response', Response::validationErrorsResponse($e->getValidationErrors()));
		}

		$model = $this->getBaseController()->save($configuration);

		if ($model === null) {
		    $this->return400(['message' => Translation::get('error/shippingmethodsave')]);

            return;
		}

		if (!$model->id || !$this->getBaseController()->activate((int)$model->id)) {
		    $this->return400(['message' => Translation::get('error/shippingmethodactivate')]);

            return;
		}

        $this->View()->assign('response', $model->toArray());
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

			$version = Shopware()->Config()->version;
            $availableTaxes = version_compare($version, '5.7.0', '<')
                ? $this->getTaxRepository()->queryAll()->execute()
                : $availableTaxes = $this->getTaxRepository()->getTaxQuery()->execute();

			/** @var Tax $tax */
			foreach ($availableTaxes as $tax) {
				$result[] = TaxClass::fromArray([
					'value' => $tax->getId(),
					'label' => $tax->getName(),
				]);
			}

            $this->View()->assign('response', Response::dtoEntitiesResponse($result));
		} catch (FrontDtoValidationException $e) {
            $this->View()->assign('response', Response::validationErrorsResponse($e->getValidationErrors()));
		}
	}

	protected function return400($data)
    {
        $this->Response()->setStatusCode(400);
        $this->View()->assign('response', $data);
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

	/**
	 * @return ShippingMethodController
	 */
	protected function getBaseController()
	{
		if ($this->baseController === null) {
			$this->baseController = new ShippingMethodController();
		}

		return $this->baseController;
	}
}
