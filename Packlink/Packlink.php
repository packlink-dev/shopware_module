<?php

namespace Packlink;

require_once __DIR__ . '/vendor/autoload.php';

use Doctrine\ORM\EntityManager;
use Logeecom\Infrastructure\Logger\Logger;
use Logeecom\Infrastructure\ORM\RepositoryRegistry;
use Logeecom\Infrastructure\ServiceRegister;
use Packlink\Bootstrap\Bootstrap;
use Packlink\Bootstrap\Database;
use Packlink\BusinessLogic\Configuration;
use Packlink\BusinessLogic\ShippingMethod\Utility\ShipmentStatus;
use Packlink\Entities\ShippingMethodMap;
use Shopware\Components\Plugin;
use Shopware\Components\Plugin\Context\InstallContext;
use Shopware\Components\Plugin\Context\UninstallContext;
use Shopware\Models\Dispatch\Dispatch;
use Shopware\Models\Order\Status;
use Symfony\Component\DependencyInjection\ContainerBuilder;

require_once __DIR__ . '/CSRFWhitelistAware.php';

class Packlink extends Plugin
{
    /**
     * @var Configuration
     */
    protected $configService;

    /**
     * @inheritDoc
     */
    public function build(ContainerBuilder $container)
    {
        $container->setParameter('packlink.plugin_dir', $this->getPath());
        parent::build($container);
    }

    /**
     * Performs plugin installation.
     *
     * @param InstallContext $context
     *
     * @throws \Logeecom\Infrastructure\TaskExecution\Exceptions\TaskRunnerStatusStorageUnavailableException
     */
    public function install(InstallContext $context)
    {
        Bootstrap::init();

        /** @var EntityManager $entityManager */
        $entityManager = $this->container->get('models');
        $db = new Database($entityManager);
        $db->install();
        Logger::logInfo("Database for version [{$context->getCurrentVersion()}] created...", 'Integration');

        $this->setDefaultConfig();

        Logger::logInfo('Installation completed...', 'Integration');
    }

    /**
     * Performs plugin uninstall.
     *
     * @param \Shopware\Components\Plugin\Context\UninstallContext $context
     *
     * @throws \Doctrine\ORM\OptimisticLockException
     * @throws \Logeecom\Infrastructure\ORM\Exceptions\RepositoryNotRegisteredException
     */
    public function uninstall(UninstallContext $context)
    {
        Bootstrap::init();

        if (!$context->keepUserData()) {
            $this->removeCreatedShippingMethods();

            /** @var EntityManager $entityManager */
            $entityManager = $this->container->get('models');
            $db = new Database($entityManager);
            $db->uninstall();
        }
    }

    /**
     * Sets default configuration.
     *
     * @throws \Logeecom\Infrastructure\TaskExecution\Exceptions\TaskRunnerStatusStorageUnavailableException
     */
    protected function setDefaultConfig()
    {
        $this->setDefaultStatusMapping();
        $this->getConfigService()->setTaskRunnerStatus('', null);
    }

    /**
     * Sets default order status mapping.
     */
    protected function setDefaultStatusMapping()
    {
        $mappings = $this->getConfigService()->getOrderStatusMappings();

        if (empty($mappings)) {
            $this->getConfigService()->setOrderStatusMappings(
                [
                    ShipmentStatus::STATUS_PENDING => '',
                    ShipmentStatus::STATUS_ACCEPTED => Status::ORDER_STATE_READY_FOR_DELIVERY,
                    ShipmentStatus::STATUS_READY => Status::ORDER_STATE_READY_FOR_DELIVERY,
                    ShipmentStatus::STATUS_IN_TRANSIT => Status::ORDER_STATE_READY_FOR_DELIVERY,
                    ShipmentStatus::STATUS_DELIVERED => Status::ORDER_STATE_COMPLETELY_DELIVERED,
                ]
            );
        }
    }

    /**
     * Retrieves configuration service;
     *
     * @return \Packlink\BusinessLogic\Configuration
     */
    protected function getConfigService()
    {
        if ($this->configService === null) {
            $this->configService = ServiceRegister::getService(Configuration::CLASS_NAME);
        }

        return $this->configService;
    }

    /**
     * Removes creates shipping methods during install.
     *
     * @throws \Logeecom\Infrastructure\ORM\Exceptions\RepositoryNotRegisteredException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    protected function removeCreatedShippingMethods()
    {
        $dispatchRepository = Shopware()->Models()->getRepository(Dispatch::class);

        $mapRepository = RepositoryRegistry::getRepository(ShippingMethodMap::getClassName());
        $maps = $mapRepository->select();

        /** @var ShippingMethodMap $map */
        foreach ($maps as $map) {
            $dispatch = $dispatchRepository->find($map->shopwareCarrierId);
            if ($dispatch) {
                Shopware()->Models()->remove($dispatch);
            }
        }

        Shopware()->Models()->flush();
    }
}