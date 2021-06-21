<?php

use Packlink\Controllers\Common\CanInstantiateServices;
use Shopware\Components\CSRFWhitelistAware;

class Shopware_Controllers_Backend_PacklinkMain extends Enlight_Controller_Action implements CSRFWhitelistAware
{
    use CanInstantiateServices;

    /**
     * @inheritDoc
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
        $action = $this->isLoggedIn() ? 'Configuration' : 'Login';

        $this->backendRedirect($action);
    }

    /**
     * Checks whether user is logged in.
     *
     * @return bool
     */
    protected function isLoggedIn()
    {
        $authToken = $this->getConfigService()->getAuthorizationToken();

        return !empty($authToken);
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
	    $version = Shopware()->Config()->version;
	    $backendSession = version_compare($version, '5.7.0', '<')? 'BackendSession' : 'backendsession';

	    $this->redirect(
		    [
			    'module' => 'backend',
			    'controller' => "PacklinkConfiguration",
			    'action' => 'index',
			    '__csrf_token' => $this->container->get($backendSession)->offsetGet('X-CSRF-Token'),
			    'method' => $action
		    ]
	    );
    }
}