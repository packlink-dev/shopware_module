<?php

namespace Packlink\Bootstrap;

use Packlink\Brands\Packlink\PacklinkConfigurationService;
use Packlink\BusinessLogic\BootstrapComponent;
use Packlink\BusinessLogic\Brand\BrandConfigurationService;
use Packlink\BusinessLogic\Order\Interfaces\ShopOrderService as ShopOrderServiceInterface;
use Packlink\BusinessLogic\OrderShipmentDetails\Models\OrderShipmentDetails;
use Packlink\BusinessLogic\Registration\RegistrationInfoService;
use Packlink\BusinessLogic\Scheduler\Models\Schedule;
use Packlink\BusinessLogic\ShipmentDraft\Models\OrderSendDraftTaskMap;
use Packlink\BusinessLogic\ShippingMethod\Interfaces\ShopShippingMethodService;
use Packlink\BusinessLogic\ShippingMethod\Models\ShippingMethod;
use Packlink\Contracts\Services\BusinessLogic\DebugService;
use Packlink\Entities\OrderDropoffMap;
use Packlink\Entities\ShippingMethodMap;
use Packlink\Infrastructure\Configuration\ConfigEntity;
use Packlink\Infrastructure\Configuration\Configuration;
use Packlink\Infrastructure\Http\CurlHttpClient;
use Packlink\Infrastructure\Http\HttpClient;
use Packlink\Infrastructure\Logger\Interfaces\ShopLoggerAdapter;
use Packlink\Infrastructure\Logger\LogData;
use Packlink\Infrastructure\ORM\RepositoryRegistry;
use Packlink\Infrastructure\Serializer\Concrete\NativeSerializer;
use Packlink\Infrastructure\Serializer\Serializer;
use Packlink\Infrastructure\ServiceRegister;
use Packlink\Infrastructure\TaskExecution\Process;
use Packlink\Infrastructure\TaskExecution\QueueItem;
use Packlink\Repositories\BaseRepository;
use Packlink\Repositories\QueueItemRepository;
use Packlink\Services\BusinessLogic\ConfigurationService;
use Packlink\Services\BusinessLogic\ShopOrderService;
use Packlink\Services\BusinessLogic\ShopShippingMethodService as ConcreteShopShippingMethodService;
use Packlink\BusinessLogic\SystemInformation\SystemInfoService as SystemInfoInterface;
use Packlink\Services\BusinessLogic\SystemInfoService;
use Packlink\Services\Infrastructure\LoggerService;

class Bootstrap extends BootstrapComponent
{
    /**
     * @inheritDoc
     */
    protected static function initServices()
    {
        parent::initServices();

        ServiceRegister::registerService(
            Serializer::CLASS_NAME,
            function () {
                return new NativeSerializer();
            }
        );

        ServiceRegister::registerService(
            ShopLoggerAdapter::CLASS_NAME,
            function () {
                return LoggerService::getInstance();
            }
        );

        ServiceRegister::registerService(
            Configuration::CLASS_NAME,
            function () {
                return ConfigurationService::getInstance();
            }
        );

        ServiceRegister::registerService(
            BrandConfigurationService::CLASS_NAME,
            static function () {
                return new PacklinkConfigurationService();
            }
        );

        ServiceRegister::registerService(
            HttpClient::CLASS_NAME,
            function () {
                return new CurlHttpClient();
            }
        );

        ServiceRegister::registerService(
            ShopOrderServiceInterface::CLASS_NAME,
            function () {
                return new ShopOrderService();
            }
        );

        ServiceRegister::registerService(
            ShopShippingMethodService::CLASS_NAME,
            function () {
                return new ConcreteShopShippingMethodService();
            }
        );

        ServiceRegister::registerService(
            DebugService::class,
            function () {
                return new \Packlink\Services\BusinessLogic\DebugService();
            }
        );

        ServiceRegister::registerService(
            RegistrationInfoService::class,
            function () {
                return new \Packlink\Services\BusinessLogic\RegistrationInfoService();
            });

        ServiceRegister::registerService(
            SystemInfoInterface::class,
            function () {
                return new SystemInfoService();
            }
        );
    }

    /**
     * @inheritDoc
     *
     * @throws \Packlink\Infrastructure\ORM\Exceptions\RepositoryClassException
     */
    protected static function initRepositories()
    {
        parent::initRepositories();

        RepositoryRegistry::registerRepository(Process::getClassName(), BaseRepository::getClassName());
        RepositoryRegistry::registerRepository(Schedule::getClassName(), BaseRepository::getClassName());
        RepositoryRegistry::registerRepository(ConfigEntity::getClassName(), BaseRepository::getClassName());
        RepositoryRegistry::registerRepository(OrderShipmentDetails::getClassName(), BaseRepository::getClassName());
        RepositoryRegistry::registerRepository(QueueItem::getClassName(), QueueItemRepository::getClassName());
        RepositoryRegistry::registerRepository(ShippingMethodMap::getClassName(), BaseRepository::getClassName());
        RepositoryRegistry::registerRepository(ShippingMethod::getClassName(), BaseRepository::getClassName());
        RepositoryRegistry::registerRepository(OrderDropoffMap::getClassName(), BaseRepository::getClassName());
        RepositoryRegistry::registerRepository(LogData::CLASS_NAME, BaseRepository::getClassName());
        RepositoryRegistry::registerRepository(OrderSendDraftTaskMap::getClassName(), BaseRepository::getClassName());
    }
}