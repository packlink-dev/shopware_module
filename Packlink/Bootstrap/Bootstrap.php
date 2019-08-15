<?php

namespace Packlink\Bootstrap;

use Logeecom\Infrastructure\Configuration\ConfigEntity;
use Logeecom\Infrastructure\Configuration\Configuration;
use Logeecom\Infrastructure\Http\CurlHttpClient;
use Logeecom\Infrastructure\Http\HttpClient;
use Logeecom\Infrastructure\Logger\Interfaces\ShopLoggerAdapter;
use Logeecom\Infrastructure\ORM\RepositoryRegistry;
use Logeecom\Infrastructure\ServiceRegister;
use Logeecom\Infrastructure\TaskExecution\Process;
use Logeecom\Infrastructure\TaskExecution\QueueItem;
use Packlink\BusinessLogic\BootstrapComponent;
use Packlink\BusinessLogic\Order\Interfaces\OrderRepository as OrderRepositoryInterface;
use Packlink\BusinessLogic\Order\Models\OrderShipmentDetails;
use Packlink\BusinessLogic\Scheduler\Models\Schedule;
use Packlink\BusinessLogic\ShippingMethod\Interfaces\ShopShippingMethodService;
use Packlink\BusinessLogic\ShippingMethod\Models\ShippingMethod;
use Packlink\Entities\ShippingMethodMap;
use Packlink\Repositories\BaseRepository;
use Packlink\Repositories\OrderRepository;
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
            OrderRepositoryInterface::CLASS_NAME,
            function () {
                return new OrderRepository();
            }
        );

        ServiceRegister::registerService(
            ShopShippingMethodService::CLASS_NAME,
            function () {
                return new ConcreteShopShippingMethodService();
            }
        );
    }

    /**
     * @inheritDoc
     *
     * @throws \Logeecom\Infrastructure\ORM\Exceptions\RepositoryClassException
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
    }
}