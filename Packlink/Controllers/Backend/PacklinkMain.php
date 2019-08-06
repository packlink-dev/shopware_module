<?php

use Shopware\Components\CSRFWhitelistAware;

class Shopware_Controllers_Backend_PacklinkMain extends Enlight_Controller_Action implements CSRFWhitelistAware
{
    /**
     * @inheritDoc
     */
    public function getWhitelistedCSRFActions()
    {
        return ['index'];
    }

    /**
     * @inheritDoc
     */
    public function postDispatch()
    {
        $csrfToken = $this->container->get('BackendSession')->offsetGet('X-CSRF-Token');
        $this->View()->assign([ 'csrfToken' => $csrfToken ]);
    }

    public function indexAction()
    {
    }

    public function testAction()
    {

    }
}