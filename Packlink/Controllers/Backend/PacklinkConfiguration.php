<?php

use Packlink\BusinessLogic\Configuration;
use Packlink\BusinessLogic\Controllers\ConfigurationController;
use Packlink\BusinessLogic\Controllers\LoginController;
use Packlink\BusinessLogic\CountryLabels\Interfaces\CountryService;
use Packlink\Infrastructure\ServiceRegister;
use Packlink\Utilities\Plugin;
use Packlink\Utilities\Request;
use Packlink\Utilities\Shop;
use Packlink\Utilities\Url;
use Shopware\Components\CSRFWhitelistAware;

/**
 * Class Shopware_Controllers_Backend_PacklinkConfiguration
 */
class Shopware_Controllers_Backend_PacklinkConfiguration extends Enlight_Controller_Action implements CSRFWhitelistAware
{
    /**
     * @inheritDoc
     */
    public function getWhitelistedCSRFActions()
    {
        return ['index', 'getData'];
    }

    public function indexAction()
    {
        Configuration::setUICountryCode($this->getLocale());

        $data = Request::getPostData();

        if (!empty($data['method']) && $data['method'] === 'Login') {
            $this->View()->assign($this->login());
        } else {
            $this->View()->assign([
                $this->getHelpUrl(),
                'version' => Plugin::getVersion(),
            ]);
        }
    }

    /**
     * Gets data for StateController initialization.
     */
    public function getDataAction()
    {
        $this->View()->assign([
            'response' => [
                'baseResourcesUrl' => '/custom/plugins/Packlink/Resources/views/backend/_resources/packlink',
                'stateUrl' => Url::getBackendUrl(
                    'PacklinkModuleStateController',
                    'getCurrentState'
                ),
                'urls' => $this->getUrls(),
                'templates' => $this->getTemplates(),
                'lang' => $this->getTranslations(),
            ]
        ]);
    }

    /**
     * Retrieves help link.
     */
    public function getHelpLinkAction()
    {
        $this->View()->assign([
            'response' => [
                'helpUrl' => $this->getHelpUrl(),
                'version' => Plugin::getVersion(),
            ]
        ]);
    }

    /**
     * Attempts to log the user in with the provided Packlink API key.
     */
    public function loginAction()
    {
        $this->View()->assign(['response' => ['success' => $this->login()]]);
    }

    /**
     * @return mixed|string
     */
    protected function getHelpUrl()
    {
        $controller = new ConfigurationController();

        return $controller->getHelpLink();
    }

    /**
     * @return bool
     */
    protected function login()
    {
        $data = Request::getPostData();
        $controller = new LoginController();

        return $controller->login(!empty($data['apiKey']) ? $data['apiKey'] : '');
    }

    /**
     * Returns Packlink module templates.
     */
    protected function getTemplates()
    {
        $baseDir = __DIR__ . '/../../Resources/views/backend/_resources/packlink/templates/';

        return [
            'pl-configuration-page' => [
                'pl-main-page-holder' => file_get_contents($baseDir . 'configuration.html'),
            ],
            'pl-countries-selection-modal' => file_get_contents($baseDir . 'countries-selection-modal.html'),
            'pl-default-parcel-page' => [
                'pl-main-page-holder' => file_get_contents($baseDir . 'default-parcel.html'),
            ],
            'pl-default-warehouse-page' => [
                'pl-main-page-holder' => file_get_contents($baseDir . 'default-warehouse.html'),
            ],
            'pl-disable-carriers-modal' => file_get_contents($baseDir . 'disable-carriers-modal.html'),
            'pl-edit-service-page' => [
                'pl-header-section' => '',
                'pl-main-page-holder' => file_get_contents($baseDir . 'edit-shipping-service.html'),
                'pl-pricing-policies' => file_get_contents($baseDir . 'pricing-policies-list.html'),
            ],
            'pl-login-page' => [
                'pl-main-page-holder' => file_get_contents($baseDir . 'login.html'),
            ],
            'pl-my-shipping-services-page' => [
                'pl-main-page-holder' => file_get_contents($baseDir . 'my-shipping-services.html'),
                'pl-header-section' => file_get_contents($baseDir . 'shipping-services-header.html'),
                'pl-shipping-services-table' => file_get_contents($baseDir . 'shipping-services-table.html'),
                'pl-shipping-services-list' => file_get_contents($baseDir . 'shipping-services-list.html'),
            ],
            'pl-onboarding-overview-page' => [
                'pl-main-page-holder' => file_get_contents($baseDir . 'onboarding-overview.html'),
            ],
            'pl-onboarding-welcome-page' => [
                'pl-main-page-holder' => file_get_contents($baseDir . 'onboarding-welcome.html'),
            ],
            'pl-order-status-mapping-page' => [
                'pl-main-page-holder' => file_get_contents($baseDir . 'order-status-mapping.html'),
            ],
            'pl-pick-service-page' => [
                'pl-header-section' => '',
                'pl-main-page-holder' => file_get_contents($baseDir . 'pick-shipping-services.html'),
                'pl-shipping-services-table' => file_get_contents($baseDir . 'shipping-services-table.html'),
                'pl-shipping-services-list' => file_get_contents($baseDir . 'shipping-services-list.html'),
            ],
            'pl-pricing-policy-modal' => file_get_contents($baseDir . 'pricing-policy-modal.html'),
            'pl-register-page' => [
                'pl-main-page-holder' => file_get_contents($baseDir . 'register.html'),
            ],
            'pl-register-modal' => file_get_contents($baseDir . 'register-modal.html'),
            'pl-system-info-modal' => file_get_contents($baseDir . 'system-info-modal.html'),
        ];
    }

    /**
     * Returns Packlink module controller URLs.
     */
    protected function getUrls()
    {
        $shopUrl = $this->request->getServer()['REQUEST_SCHEME'] . '://' . Shop::getDefaultShop()->getHost();

        return [
            'login' => [
                'submit' => Url::getBackendUrl('PacklinkConfiguration', 'login'),
                'listOfCountriesUrl' => Url::getBackendUrl('PacklinkRegistrationRegionsController', 'getRegions'),
                'logoPath' => '',
            ],
            'register' => [
                'getRegistrationData' => $shopUrl . Url::getBackendUrl(
                        'PacklinkRegistrationController',
                        'getRegisterData'
                    ),
                'submit' => Url::getBackendUrl('PacklinkRegistrationController', 'register'),
            ],
            'onboarding-state' => [
                'getState' => Url::getBackendUrl('PacklinkOnboardingController', 'getCurrentState'),
            ],
            'onboarding-welcome' => [],
            'onboarding-overview' => [
                'defaultParcelGet' => Url::getBackendUrl('PacklinkDefaultParcel', 'index'),
                'defaultWarehouseGet' => Url::getBackendUrl('PacklinkDefaultWarehouse', 'index'),
            ],
            'default-parcel' => [
                'getUrl' => Url::getBackendUrl('PacklinkDefaultParcel', 'index'),
                'submitUrl' => Url::getBackendUrl('PacklinkDefaultParcel', 'update'),
            ],
            'default-warehouse' => [
                'getUrl' => Url::getBackendUrl('PacklinkDefaultWarehouse', 'index'),
                'getSupportedCountriesUrl' => Url::getBackendUrl('PacklinkDefaultWarehouse', 'getCountries'),
                'submitUrl' => Url::getBackendUrl('PacklinkDefaultWarehouse', 'update'),
                'searchPostalCodesUrl' => Url::getBackendUrl('PacklinkDefaultWarehouse', 'search'),
            ],
            'configuration' => [
                'getDataUrl' => Url::getBackendUrl('PacklinkConfiguration', 'getHelpLink'),
            ],
            'system-info' => [
                'getStatusUrl' => Url::getBackendUrl('PacklinkDebug', 'getStatus'),
                'setStatusUrl' => Url::getBackendUrl('PacklinkDebug', 'updateStatus'),
            ],
            'order-status-mapping' => [
                'getMappingAndStatusesUrl' => Url::getBackendUrl('PacklinkOrderStatusMap', 'index'),
                'setUrl' => Url::getBackendUrl('PacklinkOrderStatusMap', 'update'),
            ],
            'my-shipping-services' => [
                'getServicesUrl' => Url::getBackendUrl('PacklinkShippingMethod', 'getActive'),
                'deleteServiceUrl' => Url::getBackendUrl('PacklinkShippingMethod', 'deactivate'),
                'getCurrencyDetailsUrl' => Url::getBackendUrl('PacklinkSystemInfoController', 'get'),
                'systemId' => (string)Shop::getDefaultShop()->getId()
            ],
            'pick-shipping-service' => [
                'getActiveServicesUrl' => Url::getBackendUrl('PacklinkShippingMethod', 'getActive'),
                'getServicesUrl' => Url::getBackendUrl('PacklinkShippingMethod', 'getInactive'),
                'getTaskStatusUrl' => Url::getBackendUrl('PacklinkShippingMethod', 'getTaskStatus'),
                'startAutoConfigureUrl' => Url::getBackendUrl('PacklinkAutoConfigure', 'index'),
                'disableCarriersUrl' => Url::getBackendUrl(
                    'PacklinkShopShippingMethod',
                    'deactivateShopShippingMethods'
                ),
                'getCurrencyDetailsUrl' => Url::getBackendUrl('PacklinkSystemInfoController', 'get'),
                'systemId' => (string)Shop::getDefaultShop()->getId()
            ],
            'edit-service' => [
                'getServiceUrl' => $shopUrl . Url::getBackendUrl('PacklinkShippingMethod', 'getShippingMethod'),
                'saveServiceUrl' => Url::getBackendUrl('PacklinkShippingMethod', 'save'),
                'getTaxClassesUrl' => Url::getBackendUrl('PacklinkShippingMethod', 'getTaxClasses'),
                'getCountriesListUrl' => Url::getBackendUrl('PacklinkShippingCountriesController', 'getAll'),
                'hasTaxConfiguration' => true,
                'hasCountryConfiguration' => true,
                'canDisplayCarrierLogos' => true,
                'getCurrencyDetailsUrl' => Url::getBackendUrl('PacklinkSystemInfoController', 'get')
            ],
        ];
    }

    /**
     * Returns Packlink module translations in the default and the current system language.
     *
     * @return array
     */
    protected function getTranslations()
    {
        $locale = $this->getLocale();

        /** @var CountryService $countryService */
        $countryService = ServiceRegister::getService(CountryService::class);
        $default = $countryService->getAllLabels('en');
        $current_lang = $countryService->getAllLabels($locale);

        return [
            'default' => $default['en'],
            'current' => $current_lang[$locale],
        ];
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