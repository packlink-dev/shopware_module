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
     * @var RegistrationController
     */
    private $baseController;


    public function __construct()
    {
        parent::__construct();

        $this->baseController = new RegistrationController();
    }

    /**
     * Returns registration data.
     */
    public function getRegisterDataAction()
    {
        $country = $this->request->getQuery('country');

        if (empty($country)) {
            Response::json(['message' => 'Not found.'], 404);
        }

        Response::json($this->baseController->getRegisterData($country));
    }

    /**
     * Attempts to register the user on Packlink PRO.
     */
    public function registerAction()
    {
        $data = Request::getPostData();
        $data['ecommerces'] = ['Shopware'];

        try {
            $status = $this->baseController->register($data);
            Response::json(['success' => $status]);
        } catch (Exception $e) {
            Response::json(['success' => false, 'error' => $e->getMessage()]);
        }
    }
}
