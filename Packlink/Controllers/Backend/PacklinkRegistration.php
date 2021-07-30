<?php

use Packlink\BusinessLogic\Country\CountryService;
use Packlink\Infrastructure\ServiceRegister;
use Packlink\Controllers\Common\CanInstantiateServices;
use Packlink\Utilities\Translation;
use Packlink\Utilities\Response;

class Shopware_Controllers_Backend_PacklinkRegistration extends Enlight_Controller_Action
{
    use CanInstantiateServices;

    /**
     * Returns countries supported by Packlink.
     */
    public function getCountriesAction()
    {
        /** @var \Packlink\BusinessLogic\Country\CountryService $countryService */
        $countryService = ServiceRegister::getService(CountryService::CLASS_NAME);
        $supportedCountries = $countryService->getSupportedCountries();

        foreach ($supportedCountries as $country) {
            $country->registrationLink = str_replace('shopware', 'pro', $country->registrationLink);
            $country->name = Translation::get("configuration/country/{$country->code}");
        }

        $this->View()->assign('response', Response::dtoEntitiesResponse($supportedCountries));
    }
}
