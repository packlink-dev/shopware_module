<?php

namespace Packlink\Services\BusinessLogic;

use Packlink\BusinessLogic\Configuration;
use Packlink\Utilities\Plugin;
use Packlink\Utilities\Url;
use Shopware;
use Shopware\Models\Shop\Shop;

class ConfigurationService extends Configuration
{
    const INTEGRATION_NAME = "Shopware";
    const DEFAULT_VERSION = '1.0.0';
    const ECOMMERCE_NAME = 'Shopware';
    const DRAFT_SOURCE = 'module_shopware';
    /**
     * @var string
     */
    protected $systemId;

    /**
     * Retrieves integration name.
     *
     * @return string Integration name.
     */
    public function getIntegrationName()
    {
        return self::INTEGRATION_NAME;
    }

    /**
     * Returns current system identifier.
     *
     * @return string Current system identifier.
     */
    public function getCurrentSystemId()
    {
        if ($this->systemId === null) {
            /** @var \Shopware\Models\Shop\Repository $repository */
            $repository = Shopware()->Models()->getRepository(Shop::class);
            $this->systemId = (string)$repository->getDefault()->getId();
        }

        return $this->systemId;
    }

    /**
     * Returns async process starter url, always in http.
     *
     * @param string $guid Process identifier.
     *
     * @return string Formatted URL of async process starter endpoint.
     */
    public function getAsyncProcessUrl($guid)
    {
        return Url::getFrontUrl('PacklinkAsyncProcess', 'run', ['guid' => $guid]);
    }

    /**
     * Returns web-hook callback URL for current system.
     *
     * @return string Web-hook callback URL.
     */
    public function getWebHookUrl()
    {
        // TODO: Implement getWebHookUrl() method.
    }

    /**
     * Returns order draft source.
     *
     * @return string Order draft source.
     */
    public function getDraftSource()
    {
        return self::DRAFT_SOURCE;
    }

    /**
     * Gets the current version of the module/integration.
     *
     * @return string The version number.
     *
     * @throws \Exception
     */
    public function getModuleVersion()
    {
        return Plugin::getVersion();
    }

    /**
     * Gets the name of the integrated e-commerce system.
     * This name is related to Packlink API which can be different from the official system name.
     *
     * @return string The e-commerce name.
     */
    public function getECommerceName()
    {
        return self::ECOMMERCE_NAME;
    }

    /**
     * Gets the current version of the integrated e-commerce system.
     *
     * @return string The version number.
     */
    public function getECommerceVersion()
    {
        return Shopware::VERSION;
    }
}