<?php

use Logeecom\Infrastructure\Logger\Logger;
use Packlink\Controllers\Common\CanInstantiateServices;
use Shopware\Components\CSRFWhitelistAware;

class Shopware_Controllers_Backend_PacklinkLogin extends Enlight_Controller_Action implements CSRFWhitelistAware
{
    use CanInstantiateServices;

    /**
     * Returns a list with actions which should not be validated for CSRF protection
     *
     * @return string[]
     */
    public function getWhitelistedCSRFActions()
    {
        return ['index'];
    }

    /**
     * Performs index action.
     *
     * @throws \Exception
     */
    public function indexAction()
    {
        if ($this->Request()->isPost()) {
            $apiKey = $this->Request()->getPost('api_key');

            try {
                $success = !empty($apiKey) && $this->getUserAccountService()->login($apiKey);
            } catch (Exception $e) {
                Logger::logError("Log in failed because: [{$e->getMessage()}].");
                $success = false;
            }

            if ($success) {
                $this->backendRedirect('Configuration');
            } else {
                /** @noinspection NullPointerExceptionInspection */
                $this->View()->assign(['isLoginFailure' => true]);
            }
        }
    }

    /**
     * Redirects to backend controller
     *
     * @param string $action
     *
     * @throws \Exception
     */
    protected function backendRedirect($action)
    {
        $this->redirect(
            [
                'module' => 'backend',
                'controller' => "Packlink{$action}",
                'action' => 'index',
            ]
        );
    }
}