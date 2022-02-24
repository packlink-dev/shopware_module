<?php

use Packlink\BusinessLogic\Controllers\RegistrationController;
use Packlink\Utilities\Request;

/**
 * Class Shopware_Controllers_Backend_PacklinkRegistrationController
 */
class Shopware_Controllers_Backend_PacklinkRegistrationController extends Enlight_Controller_Action
{
    /**
     * @var RegistrationController
     */
    private $baseController;

    /**
     * Returns registration data.
     */
    public function getRegisterDataAction()
    {
        $country = $this->request->getQuery('country');

        if (empty($country)) {
            $this->Response()->setStatusCode(404);
            $this->View()->assign(['message' => 'Not found.']);

            return;
        }

        $this->View()->assign('response', $this->getBaseController()->getRegisterData($country));
    }

    /**
     * Attempts to register the user on Packlink PRO.
     */
    public function registerAction()
    {
        $data = Request::getPostData();
        $data['ecommerces'] = ['Shopware'];

        try {
            $status = $this->getBaseController()->register($data);
            $this->View()->assign('response', ['success' => $status]);
        } catch (Exception $e) {
            $this->View()->assign('response', [
                'success' => false,
                'error' => $e->getMessage() === 'Registration failed. Error: ' ?
                    'Registration failed. Error: Invalid phone number.' : $e->getMessage(),
                ]);
        }
    }

    /**
     * @return RegistrationController
     */
    private function getBaseController()
    {
        if ($this->baseController === null) {
            $this->baseController = new RegistrationController();
        }

        return $this->baseController;
    }
}
