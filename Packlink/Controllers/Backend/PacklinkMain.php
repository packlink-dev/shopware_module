<?php

use Logeecom\Infrastructure\ServiceRegister;
use Packlink\BusinessLogic\Configuration;
use Shopware\Components\CSRFWhitelistAware;

class Shopware_Controllers_Backend_PacklinkMain extends Enlight_Controller_Action implements CSRFWhitelistAware
{
    /** @var \Packlink\Services\BusinessLogic\ConfigurationService */
    protected $configService;

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
        $this->redirect(
            [
                'module' => 'backend',
                'controller' => "Packlink{$action}",
                'action' => 'index'
            ]
        );
    }

    /**
     * Retrieves configuration service.
     *
     * @return \Packlink\Services\BusinessLogic\ConfigurationService
     */
    protected function getConfigService()
    {
        if ($this->configService === null) {
            $this->configService = ServiceRegister::getService(Configuration::CLASS_NAME);
        }

        return $this->configService;
    }
}