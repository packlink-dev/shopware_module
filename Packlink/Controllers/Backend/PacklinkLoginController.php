<?php

use Packlink\BusinessLogic\Controllers\LoginController;
use Packlink\Utilities\Request;
use Packlink\Utilities\Response;


/**
 * Class Shopware_Controllers_Backend_PacklinkLoginController
 */
class Shopware_Controllers_Backend_PacklinkLoginController extends Enlight_Controller_Action
{
    /**
     * Attempts to log the user in with the provided Packlink API key.
     */
    public function loginAction()
    {
        $data = Request::getPostData();
        $controller = new LoginController();

        $status = $controller->login(!empty($data['apiKey']) ? $data['apiKey'] : '');

        Response::json(['success' => $status]);
    }
}