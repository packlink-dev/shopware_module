<?php

use Logeecom\Infrastructure\Exceptions\BaseException;
use Logeecom\Infrastructure\TaskExecution\QueueItem;
use Packlink\BusinessLogic\Controllers\DTO\ShippingMethodConfiguration;
use Packlink\BusinessLogic\Controllers\ShippingMethodController;
use Packlink\BusinessLogic\Controllers\UpdateShippingServicesTaskStatusController;
use Packlink\Controllers\Common\CanFormatResponse;
use Packlink\Controllers\Common\CanInstantiateServices;
use Packlink\Utilities\CarrierLogo;
use Packlink\Utilities\Request;
use Packlink\Utilities\Response;
use Packlink\Utilities\Translation;

class Shopware_Controllers_Backend_PacklinkShippingMethod extends Enlight_Controller_Action
{
    use CanInstantiateServices, CanFormatResponse;
    /** @var \Packlink\BusinessLogic\Controllers\ShippingMethodController */
    protected $controller;

    /**
     * Shopware_Controllers_Backend_PacklinkShippingMethod constructor.
     *
     * @param \Enlight_Controller_Request_Request $request
     * @param \Enlight_Controller_Response_Response $response
     *
     * @throws \Enlight_Event_Exception
     * @throws \Enlight_Exception
     */
    public function __construct(
        Enlight_Controller_Request_Request $request,
        Enlight_Controller_Response_Response $response
    ) {
        parent::__construct($request, $response);

        $this->controller = new ShippingMethodController();
    }

    /**
     * Retrieves all shipping methods.
     *
     * @throws \Exception
     */
    public function listAction()
    {
        $data = $this->controller->getAll();

        $result = [];
        $country = $this->getUserCountry();
        foreach ($data as $item) {
            $item->logoUrl = CarrierLogo::getLogo($country, $item->carrierName);
            $result[] = $this->formatResponse($item);
        }

        Response::json($result);
    }

    /**
     * Handles shipping method activation.
     */
    public function activateAction()
    {
        $data = Request::getPostData();

        if ($this->activateShippingMethod(array_key_exists('id', $data) ? $data['id'] : 0)) {
            Response::json(['message' => Translation::get('success/shippingmethodactivate')]);
        } else {
            Response::json(['message' => Translation::get('error/shippingmethodactivate')], 400);
        }
    }

    /**
     * Handles shipping method deactivation.
     */
    public function deactivateAction()
    {
        $data = Request::getPostData();

        if ($this->deactivateShippingMethod(array_key_exists('id', $data) ? $data['id'] : 0)) {
            Response::json(['message' => Translation::get('success/shippingmethoddeactivate')]);
        } else {
            Response::json(['message' => Translation::get('error/shippingmethoddeactivate')], 400);
        }
    }

    /**
     * Updates shipping method.
     *
     * @throws \Exception
     */
    public function updateAction()
    {
        $data = Request::getPostData();

        $model = $this->controller->save($this->transformShippingMethodForSaving($data));
        if (!$model) {
            Response::json(['message' => Translation::get('error/shippingmethodsave')], 400);
        }

        if (!$model->selected) {
            $model->selected = $this->controller->activate($model->id);
        }

        $model->logoUrl = CarrierLogo::getLogo($this->getUserCountry(), $model->carrierName);

        Response::json($this->formatResponse($model));
    }

    /**
     * Retrieves status of update shipping services task.
     */
    public function getStatusAction()
    {
        $status = QueueItem::FAILED;

        $controller = new UpdateShippingServicesTaskStatusController();
        try {
            $status = $controller->getLastTaskStatus();
        } catch (BaseException $e) {
        }

        Response::json(['status' => $status]);
    }

    /**
     * Activates shipping method.
     *
     * @param int $id
     *
     * @return bool
     */
    protected function activateShippingMethod($id)
    {
        return $id && $this->controller->activate($id);
    }

    /**
     * Deactivates shipping method.
     *
     * @param int $id
     *
     * @return bool
     */
    protected function deactivateShippingMethod($id)
    {
        return $id && $this->controller->deactivate($id);
    }

    /**
     * Transforms shipping method for saving.
     *
     * @param array $data
     *
     * @return \Packlink\BusinessLogic\Controllers\DTO\ShippingMethodConfiguration
     */
    protected function transformShippingMethodForSaving(array $data)
    {
        $data['taxClass'] = (int)$data['taxClass'];

        return ShippingMethodConfiguration::fromArray($data);
    }

    /**
     * Retrieves user country. Fallback is de.
     *
     * @return string
     */
    protected function getUserCountry()
    {
        $userAccount = $this->getConfigService()->getUserInfo();

        return strtolower($userAccount ? $userAccount->country : 'de');
    }
}