<?php

use Packlink\BusinessLogic\Controllers\RegistrationController;
use Packlink\Utilities\Request;
use Packlink\Utilities\Response;


/**
 * Class Shopware_Controllers_Backend_PacklinkRegistrationController
 */
class Shopware_Controllers_Backend_PacklinkRegistrationController extends Enlight_Controller_Action
{
    /**
     * Returns registration data.
     */
    public function getRegisterDataAction()
    {
        $data = Request::getPostData();

        if (empty($data['country'])) {
            Response::json(['message' => 'Not found.'], 404);
        }

        Response::json($this->getBaseController()->getRegisterData($data['country']));
    }

    /**
     * Attempts to register the user on Packlink PRO.
     */
    public function registerAction()
    {
        $data = Request::getPostData();
        $data['ecommerces'] = 'Shopware';

        try {
            $status = $this->getBaseController()->register($data);
            Response::json(['success' => $status]);
        } catch (Exception $e) {
            Response::json(['success' => false, 'error' => $e->getMessage()]);
        }
    }

    /**
     * @return RegistrationController
     */
    protected function getBaseController()
    {
        return new RegistrationController();
    }
}