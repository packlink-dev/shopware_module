<?php

namespace Packlink\Bootstrap;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Tools\SchemaTool;
use Packlink\Core\Infrastructure\Logger\Logger;
use Packlink\Core\Infrastructure\ORM\RepositoryRegistry;
use Packlink\Core\Infrastructure\ServiceRegister;
use Packlink\Core\BusinessLogic\Configuration;
use Packlink\Core\BusinessLogic\ShippingMethod\Utility\ShipmentStatus;
use Packlink\Entities\ShippingMethodMap;
use Packlink\Models\PacklinkEntity;
use Packlink\Utilities\VersionedFileReader;
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
     * @throws \Packlink\Core\Infrastructure\TaskExecution\Exceptions\TaskRunnerStatusStorageUnavailableException
     */
    public function install()
    {
        $this->schemaTool->updateSchema($this->getClassesMetaData(), true);

        $this->setDefaultData();
    }

    /**
     * Drops created tables and removes residual data.
     *
     * @throws \Packlink\Core\Infrastructure\ORM\Exceptions\RepositoryNotRegisteredException
     */
    public function uninstall()
    {
        $this->removeData();

        $this->schemaTool->dropSchema($this->getClassesMetaData());
    }

    /**
     * Performs activation logic.
     *
     * @throws \Packlink\Core\Infrastructure\ORM\Exceptions\RepositoryNotRegisteredException
     */
    public function activate()
    {
        $this->setPacklinkCarriersStatus(true);
    }

    /**
     * Performs database update.
     *
     * @param \Packlink\Utilities\VersionedFileReader $versionedFileReader
     *
     * @return bool
     */
    public function update(VersionedFileReader $versionedFileReader)
    {
        $db = Shopware()->Db();

        if ($db === null) {
            Logger::logError('Failed to perform database update because: Database is not connected.');

            return false;
        }

        while (($statements = $versionedFileReader->readNext())) {
            foreach ($statements as $statement) {
                try {
                    $db->executeQuery($statement);
                } catch (\Exception $e) {
                    Logger::logError("Failed to perform database update because: {$e->getMessage()}");

                    return false;
                }
            }
        }

        return true;
    }

    /**
     * Performs deactivation logic.
     *
     * @throws \Packlink\Core\Infrastructure\ORM\Exceptions\RepositoryNotRegisteredException
     */
    public function deactivate()
    {
        $this->setPacklinkCarriersStatus(false);
    }

    /**
     * Sets default data.
     *
     * @throws \Packlink\Core\Infrastructure\TaskExecution\Exceptions\TaskRunnerStatusStorageUnavailableException
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
     * @throws \Packlink\Core\Infrastructure\ORM\Exceptions\RepositoryNotRegisteredException
     */
    protected function removeData()
    {
        $this->removeCreatedShippingMethods();
    }

    /**
     * Sets status for Shopware's dispatches that are created by Packlink plugin.
     *
     * @param boolean $isActive
     *
     * @throws \Packlink\Core\Infrastructure\ORM\Exceptions\RepositoryNotRegisteredException
     */
    protected function setPacklinkCarriersStatus($isActive)
    {
        $status = (int) $isActive;

        $ids = $this->getPacklinkCarrierIds();

        if (!empty($ids)) {
            $this->entityManager->createQuery(
                'UPDATE ' . Dispatch::class . ' d SET d.active=' . $status . ' WHERE d.id in (' . implode(',', $ids) . ')'
            )->execute();
        }
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
     * @throws \Packlink\Core\Infrastructure\TaskExecution\Exceptions\TaskRunnerStatusStorageUnavailableException
     */
    protected function initializeTaskRunner()
    {
        $this->getConfigService()->setTaskRunnerStatus('', null);
    }

    /**
     * Removes creates shipping methods during install.
     *
     * @throws \Packlink\Core\Infrastructure\ORM\Exceptions\RepositoryNotRegisteredException
     */
    protected function removeCreatedShippingMethods()
    {

        $ids = $this->getPacklinkCarrierIds();

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

    /**
     * Retrieves list of Shopware's carrier's ids that are created by Packlink.
     *
     * @return array
     *
     * @throws \Packlink\Core\Infrastructure\ORM\Exceptions\RepositoryNotRegisteredException
     */
    protected function getPacklinkCarrierIds()
    {
        $mapRepository = RepositoryRegistry::getRepository(ShippingMethodMap::getClassName());
        $maps = $mapRepository->select();

        $ids = array_map(
            function (ShippingMethodMap $map) {
                return $map->shopwareCarrierId;
            },
            $maps
        );

        if ($backupId = $this->getConfigService()->getBackupCarrierId()) {
            $ids[] = $backupId;
        }

        return $ids;
    }
}