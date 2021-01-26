<?php

use Packlink\Infrastructure\Logger\Logger;
use Packlink\Controllers\Common\CanInstantiateServices;

class Shopware_Controllers_Backend_PacklinkLogin extends Enlight_Controller_Action
{
    use CanInstantiateServices;

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
        } else {
            $backendSession = $this->getBackendSession();

            $this->View()->assign(['csrfToken' => $this->container->get($backendSession)->offsetGet('X-CSRF-Token')]);
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
        $backendSession = $this->getBackendSession();

        $this->redirect(
            [
                'module' => 'backend',
                'controller' => "Packlink{$action}",
                'action' => 'index',
                '__csrf_token' => $this->container->get($backendSession)->offsetGet('X-CSRF-Token'),
            ]
        );
    }

    /**
     * @return string
     */
    protected function getBackendSession()
    {
        $version = Shopware()->Config()->version;
        return version_compare($version, '5.7.0', '<') ? 'BackendSession' : 'backendsession';
    }
}