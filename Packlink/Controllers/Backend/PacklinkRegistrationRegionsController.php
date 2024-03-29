<?php

use Packlink\BusinessLogic\Configuration;
use Packlink\BusinessLogic\Controllers\RegistrationRegionsController;
use Packlink\Utilities\Response;

/**
 * Class Shopware_Controllers_Backend_PacklinkRegistrationRegionsController
 */
class Shopware_Controllers_Backend_PacklinkRegistrationRegionsController extends Enlight_Controller_Action
{
    /**
     * Returns regions available for Packlink account registration.
     */
    public function getRegionsAction()
    {
        Configuration::setUICountryCode($this->getLocale());
        $controller = new RegistrationRegionsController();

        $this->View()->assign('response', Response::dtoEntitiesResponse($controller->getRegions()));
    }

    /**
     * @return string
     */
    protected function getLocale()
    {
        $locale = 'en';

        if ($auth = Shopware()->Container()->get('auth')) {
            $locale = substr($auth->getIdentity()->locale->getLocale(), 0, 2);
        }

        return in_array($locale, ['en', 'de', 'es', 'fr', 'it']) ? $locale : 'en';
    }
}