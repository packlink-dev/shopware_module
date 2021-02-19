<?php

use Packlink\BusinessLogic\Controllers\ConfigurationController;
use Packlink\BusinessLogic\Controllers\LoginController;
use Packlink\Utilities\Request;
use Packlink\Utilities\Response;
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
        $data = Request::getPostData();

        if ($data['method'] === 'Login') {
            $this->View()->assign($this->login());
        } else {
            $this->View()->assign($this->getHelpUrl());
        }
    }

    /**
     * Gets data for StateController initialization.
     */
    public function getDataAction()
    {
        $csrfToken = $this->container->get('BackendSession')->offsetGet('X-CSRF-Token');

        Response::json([
            'baseResourcesUrl' => '/custom/plugins/Packlink/Resources/views/backend/_resources/packlink',
            'stateUrl' => Url::getBackendUrl('PacklinkModuleStateController', 'getCurrentState', ['__csrf_token' => $csrfToken]),
            'urls' => $this->getUrls(),
            'templates' => $this->getTemplates(),
            'lang' => $this->getTranslations(),
        ]);
    }

    /**
     * Retrieves help link.
     */
    public function getHelpLinkAction()
    {
        Response::json(['helpUrl' => $this->getHelpUrl()]);
    }

    /**
     * Attempts to log the user in with the provided Packlink API key.
     */
    public function loginAction()
    {
        Response::json(['success' => $this->login()]);
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
        $csrfToken = $this->container->get('BackendSession')->offsetGet('X-CSRF-Token');
        $shopUrl = $this->request->getServer()['REQUEST_SCHEME'] . '://' . Shop::getDefaultShop()->getHost();

        return [
            'login' => [
                'submit' => Url::getBackendUrl('PacklinkConfiguration', 'login', ['__csrf_token' => $csrfToken]),
                'listOfCountriesUrl' => Url::getBackendUrl('PacklinkRegistrationRegionsController', 'getRegions', ['__csrf_token' => $csrfToken]),
                'logoPath' => '',
            ],
            'register' => [
                'getRegistrationData' => $shopUrl . Url::getBackendUrl('PacklinkRegistrationController', 'getRegisterData', ['__csrf_token' => $csrfToken]),
                'submit' => Url::getBackendUrl('PacklinkRegistrationController', 'register', ['__csrf_token' => $csrfToken]),
            ],
            'onboarding-state' => [
                'getState' => Url::getBackendUrl('PacklinkOnboardingController', 'getCurrentState', ['__csrf_token' => $csrfToken]),
            ],
            'onboarding-welcome' => [],
            'onboarding-overview' => [
                'defaultParcelGet' => Url::getBackendUrl('PacklinkDefaultParcel', 'index', ['__csrf_token' => $csrfToken]),
                'defaultWarehouseGet' => Url::getBackendUrl('PacklinkDefaultWarehouse', 'index', ['__csrf_token' => $csrfToken]),
            ],
            'default-parcel' => [
                'getUrl' => Url::getBackendUrl('PacklinkDefaultParcel', 'index', ['__csrf_token' => $csrfToken]),
                'submitUrl' => Url::getBackendUrl('PacklinkDefaultParcel', 'update', ['__csrf_token' => $csrfToken]),
            ],
            'default-warehouse' => [
                'getUrl' => Url::getBackendUrl('PacklinkDefaultWarehouse', 'index', ['__csrf_token' => $csrfToken]),
                'getSupportedCountriesUrl' => Url::getBackendUrl('PacklinkDefaultWarehouse', 'getCountries', ['__csrf_token' => $csrfToken]),
                'submitUrl' => Url::getBackendUrl('PacklinkDefaultWarehouse', 'update', ['__csrf_token' => $csrfToken]),
                'searchPostalCodesUrl' => Url::getBackendUrl('PacklinkDefaultWarehouse', 'search', ['__csrf_token' => $csrfToken]),
            ],
            'configuration' => [
                'getDataUrl' => Url::getBackendUrl('PacklinkConfiguration', 'getHelpLink', ['__csrf_token' => $csrfToken]),
            ],
            'system-info' => [
                'getStatusUrl' => Url::getBackendUrl('PacklinkDebug', 'getStatus', ['__csrf_token' => $csrfToken]),
                'setStatusUrl' => Url::getBackendUrl('PacklinkDebug', 'updateStatus', ['__csrf_token' => $csrfToken]),
            ],
            'order-status-mapping' => [
                'getMappingAndStatusesUrl' => Url::getBackendUrl('PacklinkOrderStatusMap', 'index', ['__csrf_token' => $csrfToken]),
                'setUrl' => Url::getBackendUrl('PacklinkOrderStatusMap', 'update', ['__csrf_token' => $csrfToken]),
            ],
            'my-shipping-services' => [
                'getServicesUrl' => Url::getBackendUrl('PacklinkShippingMethod', 'getActive', ['__csrf_token' => $csrfToken]),
                'deleteServiceUrl' => Url::getBackendUrl('PacklinkShippingMethod', 'deactivate', ['__csrf_token' => $csrfToken]),
            ],
            'pick-shipping-service' => [
                'getActiveServicesUrl' => Url::getBackendUrl('PacklinkShippingMethod', 'getActive', ['__csrf_token' => $csrfToken]),
                'getServicesUrl' => Url::getBackendUrl('PacklinkShippingMethod', 'getInactive', ['__csrf_token' => $csrfToken]),
                'getTaskStatusUrl' => Url::getBackendUrl('PacklinkShippingMethod', 'getTaskStatus', ['__csrf_token' => $csrfToken]),
                'startAutoConfigureUrl' => Url::getBackendUrl('PacklinkAutoConfigure', 'index', ['__csrf_token' => $csrfToken]),
                'disableCarriersUrl' => Url::getBackendUrl('PacklinkShopShippingMethod', 'deactivateShopShippingMethods', ['__csrf_token' => $csrfToken]),
            ],
            'edit-service' => [
                'getServiceUrl' => $shopUrl . Url::getBackendUrl('PacklinkShippingMethod', 'getShippingMethod', ['__csrf_token' => $csrfToken]),
                'saveServiceUrl' => Url::getBackendUrl('PacklinkShippingMethod', 'save', ['__csrf_token' => $csrfToken]),
                'getTaxClassesUrl' => Url::getBackendUrl('PacklinkShippingMethod', 'getTaxClasses', ['__csrf_token' => $csrfToken]),
                'getCountriesListUrl' => Url::getBackendUrl('PacklinkShippingCountriesController', 'getAll', ['__csrf_token' => $csrfToken]),
                'hasTaxConfiguration' => true,
                'hasCountryConfiguration' => true,
                'canDisplayCarrierLogos' => true,
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
        return [
            'default' => $this->getDefaultTranslations(),
            'current' => $this->getCurrentTranslations()
        ];
    }

    /**
     * Returns JSON encoded module page translations in the default language and some module-specific translations.
     *
     * @return mixed
     */
    protected function getDefaultTranslations()
    {
        $baseDir = __DIR__ . '/../../Resources/views/backend/_resources/packlink/lang/';

        return json_decode(file_get_contents($baseDir . 'en.json'), true);
    }

    /**
     * Returns JSON encoded module page translations in the current language and some module-specific translations.
     *
     * @return mixed
     */
    protected function getCurrentTranslations()
    {
        $baseDir = __DIR__ . '/../../Resources/views/backend/_resources/packlink/lang/';
        $locale = $this->request->getLocale();

        return json_decode(file_get_contents($baseDir . $locale . '.json'), true);
    }
}