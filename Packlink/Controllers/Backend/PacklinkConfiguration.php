<?php

use Packlink\Infrastructure\ServiceRegister;
use Packlink\BusinessLogic\Country\CountryService;
use Packlink\Controllers\Common\CanInstantiateServices;

class Shopware_Controllers_Backend_PacklinkConfiguration extends Enlight_Controller_Action
{
    use CanInstantiateServices;
    /**
     * List of help URLs for different country codes.
     *
     * @var array
     */
    protected static $helpUrls = [
        'EN' => 'https://support-pro.packlink.com/hc/en-gb',
        'ES' => 'https://support-pro.packlink.com/hc/es-es',
        'DE' => 'https://support-pro.packlink.com/hc/de',
        'FR' => 'https://support-pro.packlink.com/hc/fr-fr',
        'IT' => 'https://support-pro.packlink.com/hc/it',
    ];
    /**
     * List of terms and conditions URLs for different country codes.
     *
     * @var array
     */
    protected static $termsAndConditionsUrls = [
        'EN' => 'https://support-pro.packlink.com/hc/en-gb/articles/360010011480',
        'ES' => 'https://pro.packlink.es/terminos-y-condiciones/',
        'DE' => 'https://pro.packlink.de/agb/',
        'FR' => 'https://pro.packlink.fr/conditions-generales/',
        'IT' => 'https://pro.packlink.it/termini-condizioni/',
    ];

    /**
     * Renders configuration page.
     *
     * @throws \Exception
     */
    public function indexAction()
    {
        $userInfo = $this->getConfigService()->getUserInfo();
        /** @var \Packlink\BusinessLogic\Country\CountryService $countryService */
        $countryService = ServiceRegister::getService(CountryService::CLASS_NAME);

        $urlKey = 'EN';
        if ($userInfo && $countryService->isBaseCountry($userInfo->country)) {
            $urlKey = $userInfo->country;
        }

        $version = Shopware()->Config()->version;
        $backendSession = version_compare($version, '5.7.0', '<') ? 'BackendSession' : 'backendsession';

        $this->View()->assign(
            [
                'helpUrl' => self::$helpUrls[$urlKey],
                'termsUrl' => self::$termsAndConditionsUrls[$urlKey],
                'pluginVersion' => $this->configService->getModuleVersion(),
                'csrfToken' => $this->container->get($backendSession)->offsetGet('X-CSRF-Token'),
            ]
        );
    }
}
