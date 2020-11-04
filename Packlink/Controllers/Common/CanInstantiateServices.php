<?php

namespace Packlink\Controllers\Common;

use Packlink\Core\Infrastructure\Configuration\Configuration;
use Packlink\Core\Infrastructure\ORM\RepositoryRegistry;
use Packlink\Core\Infrastructure\ServiceRegister;
use Packlink\Core\Infrastructure\TaskExecution\QueueService;
use Packlink\Core\BusinessLogic\Http\Proxy;
use Packlink\Core\BusinessLogic\Location\LocationService;
use Packlink\Core\BusinessLogic\OrderShipmentDetails\Models\OrderShipmentDetails;
use Packlink\Core\BusinessLogic\Order\OrderService;
use Packlink\Core\BusinessLogic\ShippingMethod\Models\ShippingMethod;
use Packlink\Core\BusinessLogic\User\UserAccountService;
use Packlink\Contracts\Services\BusinessLogic\DebugService;
use Packlink\Entities\ShippingMethodMap;
use Packlink\Services\BusinessLogic\CheckoutService;
use Shopware\Models\Order\Order;

trait CanInstantiateServices
{
    /** @var \Packlink\Services\BusinessLogic\ConfigurationService */
    protected $configService;
    /** @var \Packlink\Core\BusinessLogic\User\UserAccountService */
    protected $userAccountService;
    /** @var \Packlink\Core\BusinessLogic\Location\LocationService */
    protected $locationService;
    /** @var \Packlink\Core\BusinessLogic\Http\Proxy */
    protected $proxy;
    /** @var \Packlink\Contracts\Services\BusinessLogic\DebugService */
    protected $debugService;
    /** @var CheckoutService */
    protected $checkoutService;
    /** @var \Packlink\Core\Infrastructure\ORM\Interfaces\RepositoryInterface */
    protected $orderDetailsRepository;
    /** @var \Packlink\Repositories\QueueItemRepository */
    protected $queueItemRepository;
    /** @var QueueService */
    protected $queueService;
    /** @var \Packlink\Core\Infrastructure\ORM\Interfaces\RepositoryInterface */
    protected $shippingMethodMapRepository;
    /** @var \Packlink\Core\Infrastructure\ORM\Interfaces\RepositoryInterface */
    protected $shippingMethodRepository;
    /** @var \Packlink\Core\BusinessLogic\Order\OrderService */
    protected $orderService;

    /**
     * Retrieves configuration service.
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
     * Retrieves user account service.
     *
     * @return \Packlink\Core\BusinessLogic\User\UserAccountService
     */
    protected function getUserAccountService()
    {
        if ($this->userAccountService === null) {
            $this->userAccountService = ServiceRegister::getService(UserAccountService::CLASS_NAME);
        }

        return $this->userAccountService;
    }

    /**
     * Retrieves location service.
     *
     * @return \Packlink\Core\BusinessLogic\Location\LocationService
     */
    protected function getLocationService()
    {
        if ($this->locationService === null) {
            $this->locationService = ServiceRegister::getService(LocationService::CLASS_NAME);
        }

        return $this->locationService;
    }

    /**
     * Retrieves proxy.
     *
     * @return \Packlink\Core\BusinessLogic\Http\Proxy
     */
    protected function getProxy()
    {
        if ($this->proxy === null) {
            $this->proxy = ServiceRegister::getService(Proxy::CLASS_NAME);
        }

        return $this->proxy;
    }

    /**
     * Retrieves debug service.
     *
     * @return \Packlink\Contracts\Services\BusinessLogic\DebugService
     */
    protected function getDebugService()
    {
        if ($this->debugService === null) {
            $this->debugService = ServiceRegister::getService(DebugService::class);
        }

        return $this->debugService;
    }

    /**
     * Retrieves checkout service.
     *
     * @return \Packlink\Services\BusinessLogic\CheckoutService
     */
    protected function getCheckoutService()
    {
        if ($this->checkoutService === null) {
            $this->checkoutService = new CheckoutService();
        }

        return $this->checkoutService;
    }

    /**
     * Retrieves order repository interface.
     *
     * @return \Packlink\Core\Infrastructure\ORM\Interfaces\RepositoryInterface
     *
     * @throws \Packlink\Core\Infrastructure\ORM\Exceptions\RepositoryNotRegisteredException
     */
    protected function getOrderDetailsRepository()
    {
        if ($this->orderDetailsRepository === null) {
            $this->orderDetailsRepository = RepositoryRegistry::getRepository(OrderShipmentDetails::getClassName());
        }

        return $this->orderDetailsRepository;
    }

    /**
     * Retrieves queue item repository.
     *
     * @return \Packlink\Repositories\QueueItemRepository
     *
     * @throws \Packlink\Core\Infrastructure\ORM\Exceptions\RepositoryClassException
     * @throws \Packlink\Core\Infrastructure\ORM\Exceptions\RepositoryNotRegisteredException
     */
    protected function getQueueItemRepository()
    {
        if ($this->queueItemRepository === null) {
            $this->queueItemRepository = RepositoryRegistry::getQueueItemRepository();
        }

        return $this->queueItemRepository;
    }

    /**
     * Retrieves queue service.
     *
     * @return \Packlink\Core\Infrastructure\TaskExecution\QueueService
     */
    protected function getQueueService()
    {
        if ($this->queueService === null) {
            $this->queueService = ServiceRegister::getService(QueueService::CLASS_NAME);
        }

        return $this->queueService;
    }

    /**
     * Retrieves order repository.
     *
     * @return \Shopware\Models\Order\Repository
     */
    protected function getShopwareOrderRepository()
    {
        return Shopware()->Models()->getRepository(Order::class);
    }

    /**
     * Retrieves shipping method map repository.
     *
     * @return \Packlink\Core\Infrastructure\ORM\Interfaces\RepositoryInterface
     *
     * @throws \Packlink\Core\Infrastructure\ORM\Exceptions\RepositoryNotRegisteredException
     */
    protected function getShippingMethodMapRepository()
    {
        if ($this->shippingMethodMapRepository === null) {
            $this->shippingMethodMapRepository = RepositoryRegistry::getRepository(ShippingMethodMap::getClassName());
        }

        return $this->shippingMethodMapRepository;
    }

    /**
     * Retrieves shipping method repository.
     *
     * @return \Packlink\Core\Infrastructure\ORM\Interfaces\RepositoryInterface
     * @throws \Packlink\Core\Infrastructure\ORM\Exceptions\RepositoryNotRegisteredException
     */
    protected function getShippingMethodRepository()
    {
        if ($this->shippingMethodRepository === null) {
            $this->shippingMethodRepository = RepositoryRegistry::getRepository(ShippingMethod::getClassName());
        }

        return $this->shippingMethodRepository;
    }

    /**
     * Retrieves order service.
     *
     * @return \Packlink\Core\BusinessLogic\Order\OrderService
     */
    protected function getOrderService()
    {
        if ($this->orderService === null) {
            $this->orderService = ServiceRegister::getService(OrderService::CLASS_NAME);
        }

        return $this->orderService;
    }
}
