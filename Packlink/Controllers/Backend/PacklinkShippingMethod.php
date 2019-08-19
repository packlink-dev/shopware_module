<?php

use Packlink\BusinessLogic\Controllers\DTO\ShippingMethodConfiguration;
use Packlink\BusinessLogic\Controllers\ShippingMethodController;
use Packlink\Controllers\Common\CanFormatResponse;
use Packlink\Controllers\Common\CanInstantiateServices;
use Packlink\Utilities\Request;
use Packlink\Utilities\Response;
use Packlink\Utilities\Translation;
use Packlink\Utilities\Url;
use Shopware\Components\CSRFWhitelistAware;

class Shopware_Controllers_Backend_PacklinkShippingMethod extends Enlight_Controller_Action implements CSRFWhitelistAware
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
     * Returns a list with actions which should not be validated for CSRF protection
     *
     * @return string[]
     */
    public function getWhitelistedCSRFActions()
    {
        return ['list', 'activate', 'deactivate', 'update'];
    }

    /**
     * Retrieves all shipping methods.
     */
    public function listAction()
    {
        $data = $this->controller->getAll();

        $result = [];
        $country = $this->getUserCountry();
        foreach ($data as $item) {
            $item->logoUrl = $this->getShippingMethodLogoUrl($country, $item->carrierName);
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
            Response::json(['message' => Translation::get('error/shippingmethodactivate')], 400);
        } else {
            Response::json(['message' => Translation::get('success/shippingmethodactivate')]);
        }
    }

    /**
     * Handles shipping method deactivation.
     */
    public function deactivateAction()
    {
        $data = Request::getPostData();

        if ($this->deactivateShippingMethod(array_key_exists('id', $data) ? $data['id'] : 0)) {
            Response::json(['message' => Translation::get('error/shippingmethoddeactivate')], 400);
        } else {
            Response::json(['message' => Translation::get('success/shippingmethoddeactivate')]);
        }
    }

    /**
     * Updates shipping method.
     */
    public function updateAction()
    {
        $data = Request::getPostData();

        $model = $this->controller->save($this->transformShippingMethodForSaving($data));
        if (!$model) {
            Response::json(['message' => Translation::get('error/shippingmethodsave')], 400);
        }

        if (!$this->activateShippingMethod($model->id)) {
            Response::json(['message' => Translation::get('error/shippingmethodactivate')], 400);
        }

        $model->selected = true;
        $model->logoUrl = $this->getShippingMethodLogoUrl($this->getUserCountry(), $model->carrierName);

        Response::json($this->formatResponse($model));
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
     * Retrieves shipping method url.
     *
     * @param $country
     * @param $name
     *
     * @return string
     */
    protected function getShippingMethodLogoUrl($country, $name)
    {
        $image = '/' . $country . '/' . strtolower(str_replace(' ', '-', $name)) . '.png';

        return Url::getFrontUrl('PacklinkImage', 'index') . '?image=' . $image;
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