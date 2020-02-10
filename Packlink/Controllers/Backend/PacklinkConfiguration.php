<?php

use Packlink\Controllers\Common\CanInstantiateServices;

class Shopware_Controllers_Backend_PacklinkConfiguration extends Enlight_Controller_Action
{
    use CanInstantiateServices;

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
     * Renders configuration page.
     *
     * @throws \Exception
     */
    public function indexAction()
    {
        $userInfo = $this->getConfigService()->getUserInfo();

        $urlKey = 'ES';
        if ($userInfo) {
            $urlKey = $userInfo->country;
        }

        $this->View()->assign(
            [
                'helpUrl' => self::$helpUrls[$urlKey],
                'termsUrl' => self::$termsAndConditionsUrls[$urlKey],
                'pluginVersion' => $this->configService->getModuleVersion(),
                'csrfToken' => $this->container->get('BackendSession')->offsetGet('X-CSRF-Token'),
            ]
        );
    }
}
