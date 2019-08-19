<?php

use Packlink\Controllers\Common\CanInstantiateServices;
use Packlink\Utilities\Translation;
use Shopware\Components\CSRFWhitelistAware;

class Shopware_Controllers_Backend_PacklinkConfiguration extends Enlight_Controller_Action implements CSRFWhitelistAware
{
    use CanInstantiateServices;
    /**
     * List of supported countries.
     *
     * @var array
     */
    protected static $supportedCountries = [
        'DE',
        'ES',
        'FR',
        'IT',
    ];
    /**
     * List of help URLs for different country codes.
     *
     * @var array
     */
    protected static $helpUrls = array(
        'ES' => 'https://support-pro.packlink.com/hc/es-es/sections/202755109-Prestashop',
        'DE' => 'https://support-pro.packlink.com/hc/de/sections/202755109-Prestashop',
        'FR' => 'https://support-pro.packlink.com/hc/fr-fr/sections/202755109-Prestashop',
        'IT' => 'https://support-pro.packlink.com/hc/it/sections/202755109-Prestashop',
    );
    /**
     * List of terms and conditions URLs for different country codes.
     *
     * @var array
     */
    protected static $termsAndConditionsUrls = array(
        'ES' => 'https://pro.packlink.es/terminos-y-condiciones/',
        'DE' => 'https://pro.packlink.de/agb/',
        'FR' => 'https://pro.packlink.fr/conditions-generales/',
        'IT' => 'https://pro.packlink.it/termini-condizioni/',
    );

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
     * Renders configuration page.
     *
     * @throws \Exception
     */
    public function indexAction()
    {
        $userInfo = $this->getConfigService()->getUserInfo();

        $urlKey = 'ES';
        if ($userInfo && $userInfo->country && in_array($userInfo->country, static::$supportedCountries)) {
            $this->View()->assign(
                [
                    'defaultWarehouse' => Translation::get("configuration/country/{$userInfo->country}"),
                ]
            );

            $urlKey = $userInfo->country;
        }

        $this->View()->assign(
            [
                'helpUrl' => self::$helpUrls[$urlKey],
                'termsUrl' => self::$termsAndConditionsUrls[$urlKey],
                'pluginVersion' => $this->configService->getModuleVersion(),
            ]
        );
    }
}