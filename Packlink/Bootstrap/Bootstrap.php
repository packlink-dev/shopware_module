<?php

namespace Packlink\Bootstrap;

use Packlink\Core\Infrastructure\Configuration\ConfigEntity;
use Packlink\Core\Infrastructure\Configuration\Configuration;
use Packlink\Core\Infrastructure\Http\CurlHttpClient;
use Packlink\Core\Infrastructure\Http\HttpClient;
use Packlink\Core\Infrastructure\Logger\Interfaces\ShopLoggerAdapter;
use Packlink\Core\Infrastructure\Logger\LogData;
use Packlink\Core\Infrastructure\ORM\RepositoryRegistry;
use Packlink\Core\Infrastructure\Serializer\Concrete\NativeSerializer;
use Packlink\Core\Infrastructure\Serializer\Serializer;
use Packlink\Core\Infrastructure\ServiceRegister;
use Packlink\Core\Infrastructure\TaskExecution\Process;
use Packlink\Core\Infrastructure\TaskExecution\QueueItem;
use Packlink\Core\BusinessLogic\BootstrapComponent;
use Packlink\Core\BusinessLogic\Order\Interfaces\ShopOrderService as ShopOrderServiceInterface;
use Packlink\Core\BusinessLogic\ShipmentDraft\Models\OrderSendDraftTaskMap;
use Packlink\Services\BusinessLogic\ShopOrderService;
use Packlink\Core\BusinessLogic\OrderShipmentDetails\Models\OrderShipmentDetails;
use Packlink\Core\BusinessLogic\Scheduler\Models\Schedule;
use Packlink\Core\BusinessLogic\ShippingMethod\Interfaces\ShopShippingMethodService;
use Packlink\Core\BusinessLogic\ShippingMethod\Models\ShippingMethod;
use Packlink\Contracts\Services\BusinessLogic\DebugService;
use Packlink\Entities\OrderDropoffMap;
use Packlink\Entities\ShippingMethodMap;
use Packlink\Repositories\BaseRepository;
use Packlink\Repositories\QueueItemRepository;
use Packlink\Services\BusinessLogic\ConfigurationService;
use Packlink\Services\Infrastructure\LoggerService;
use Packlink\Services\BusinessLogic\ShopShippingMethodService as ConcreteShopShippingMethodService;

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
    }

    /**
     * @inheritDoc
     *
     * @throws \Packlink\Core\Infrastructure\ORM\Exceptions\RepositoryClassException
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