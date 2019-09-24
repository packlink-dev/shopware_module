<?php

namespace Packlink\Bootstrap;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Tools\SchemaTool;
use Logeecom\Infrastructure\ORM\RepositoryRegistry;
use Logeecom\Infrastructure\ServiceRegister;
use Packlink\BusinessLogic\Configuration;
use Packlink\BusinessLogic\ShippingMethod\Utility\ShipmentStatus;
use Packlink\Entities\ShippingMethodMap;
use Packlink\Models\PacklinkEntity;
use Shopware\Models\Dispatch\Dispatch;
use Shopware\Models\Order\Status;

class Database
{
    /**
     * @var EntityManager
     */
    protected $entityManager;
    /**
     * @var \Doctrine\ORM\Tools\SchemaTool
     */
    protected $schemaTool;
    /**
     * @var \Packlink\Services\BusinessLogic\ConfigurationService
     */
    protected $configService;

    /**
     * @param EntityManager $entityManager
     */
    public function __construct(EntityManager $entityManager)
    {
        $this->entityManager = $entityManager;
        $this->schemaTool = new SchemaTool($this->entityManager);
    }

    /**
     * Installs all registered ORM classes
     *
     * @throws \Logeecom\Infrastructure\TaskExecution\Exceptions\TaskRunnerStatusStorageUnavailableException
     */
    public function install()
    {
        $this->schemaTool->updateSchema($this->getClassesMetaData(), true);

        $this->setDefaultData();
    }

    /**
     * Drops created tables and removes residual data.
     *
     * @throws \Logeecom\Infrastructure\ORM\Exceptions\RepositoryNotRegisteredException
     */
    public function uninstall()
    {
        $this->removeData();

        $this->schemaTool->dropSchema($this->getClassesMetaData());
    }

    /**
     * Sets default data.
     *
     * @throws \Logeecom\Infrastructure\TaskExecution\Exceptions\TaskRunnerStatusStorageUnavailableException
     */
    protected function setDefaultData()
    {
        if (empty($this->getConfigService()->getOrderStatusMappings())) {
            $this->setDefaultOrderStatusMappings();
        }

        if (empty($this->getConfigService()->getTaskRunnerStatus())) {
            $this->initializeTaskRunner();
        }
    }

    /**
     * Removes plugin data from Shopware.
     *
     * @throws \Logeecom\Infrastructure\ORM\Exceptions\RepositoryNotRegisteredException
     */
    protected function removeData()
    {
        $this->removeCreatedShippingMethods();
    }

    /**
     * Sets default order status mappings.
     */
    protected function setDefaultOrderStatusMappings()
    {
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

    /**
     * Initializes task runner.
     *
     * @throws \Logeecom\Infrastructure\TaskExecution\Exceptions\TaskRunnerStatusStorageUnavailableException
     */
    protected function initializeTaskRunner()
    {
        $this->getConfigService()->setTaskRunnerStatus('', null);
    }

    /**
     * Removes creates shipping methods during install.
     *
     * @throws \Logeecom\Infrastructure\ORM\Exceptions\RepositoryNotRegisteredException
     */
    protected function removeCreatedShippingMethods()
    {

        $mapRepository = RepositoryRegistry::getRepository(ShippingMethodMap::getClassName());
        $maps = $mapRepository->select();

        $ids = array_map(function (ShippingMethodMap $map) {
            return $map->shopwareCarrierId;
        }, $maps);

        if ($backupId = $this->getConfigService()->getBackupCarrierId()) {
            $ids[] = $backupId;
        }

        if (!empty($ids)) {
            $this->entityManager->createQuery(
                'delete from ' . Dispatch::class . ' d where d.id in (' . implode(',', $ids) . ')'
            )->execute();
        }
    }

    /**
     * Retrieves metadata for entities.
     *
     * @return array
     */
    protected function getClassesMetaData()
    {
        return [
            $this->entityManager->getClassMetadata(PacklinkEntity::class)
        ];
    }

    /**
     * Retrieves configuration service;
     *
     * @return \Packlink\Services\BusinessLogic\ConfigurationService
     */
    protected function getConfigService()
    {
        if ($this->configService === null) {
            $this->configService = ServiceRegister::getService(Configuration::CLASS_NAME);
        }

        return $this->configService;
    }
}