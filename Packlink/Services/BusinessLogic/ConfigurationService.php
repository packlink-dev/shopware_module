<?php

namespace Packlink\Services\BusinessLogic;

use Logeecom\Infrastructure\Logger\Logger;
use Packlink\BusinessLogic\Configuration;
use Packlink\Utilities\Plugin;
use Packlink\Utilities\Shop;
use Packlink\Utilities\Url;

class ConfigurationService extends Configuration
{
    const INTEGRATION_NAME = 'Shopware';
    const DEFAULT_VERSION = '1.0.0';
    const ECOMMERCE_NAME = 'Shopware';
    const DRAFT_SOURCE = 'module_shopware';
    const MAX_TASK_INACTIVITY_PERIOD = 60;
    const MIN_LOG_LEVEL = Logger::WARNING;

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
            $this->systemId = (string)Shop::getDefaultShop()->getId();
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
        $params = ['guid' => $guid];
        if ( $this->isAutoTestMode() ) {
            $params['auto-test'] = 1;
        }

        return Url::getFrontUrl('PacklinkAsyncProcess', 'run', $params);
    }

    /**
     * Returns web-hook callback URL for current system.
     *
     * @return string Web-hook callback URL.
     */
    public function getWebHookUrl()
    {
        return Url::getFrontUrl('PacklinkWebhooks', 'index');
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
        return Shopware()->Config()->get('version');
    }

    /**
     * Sets default carrier id.
     *
     * @param $id
     */
    public function setBackupCarrierId($id)
    {
        $this->saveConfigValue('backupCarrierId', (int)$id);
    }

    /**
     * Retrieves backup carrier id.
     *
     * @return int | null
     */
    public function getBackupCarrierId()
    {
        return $this->getConfigValue('backupCarrierId');
    }

    /**
     * Gets max inactivity period for a task in seconds.
     * After inactivity period is passed, system will fail such task as expired.
     *
     * @return int Max task inactivity period in seconds if set; otherwise, self::MAX_TASK_INACTIVITY_PERIOD.
     */
    public function getMaxTaskInactivityPeriod() {
        return parent::getMaxTaskInactivityPeriod() ?: self::MAX_TASK_INACTIVITY_PERIOD;
    }
}