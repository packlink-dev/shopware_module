<?php

namespace Packlink\Controllers\Common;

use Logeecom\Infrastructure\Configuration\Configuration;
use Logeecom\Infrastructure\ORM\RepositoryRegistry;
use Logeecom\Infrastructure\ServiceRegister;
use Logeecom\Infrastructure\TaskExecution\QueueService;
use Packlink\BusinessLogic\Http\Proxy;
use Packlink\BusinessLogic\Location\LocationService;
use Packlink\BusinessLogic\OrderShipmentDetails\Models\OrderShipmentDetails;
use Packlink\BusinessLogic\Order\OrderService;
use Packlink\BusinessLogic\ShippingMethod\Models\ShippingMethod;
use Packlink\BusinessLogic\User\UserAccountService;
use Packlink\Contracts\Services\BusinessLogic\DebugService;
use Packlink\Entities\ShippingMethodMap;
use Packlink\Services\BusinessLogic\CheckoutService;
use Shopware\Models\Order\Order;

trait CanInstantiateServices
{
    /** @var \Packlink\Services\BusinessLogic\ConfigurationService */
    protected $configService;
    /** @var \Packlink\BusinessLogic\User\UserAccountService */
    protected $userAccountService;
    /** @var \Packlink\BusinessLogic\Location\LocationService */
    protected $locationService;
    /** @var \Packlink\BusinessLogic\Http\Proxy */
    protected $proxy;
    /** @var \Packlink\Contracts\Services\BusinessLogic\DebugService */
    protected $debugService;
    /** @var CheckoutService */
    protected $checkoutService;
    /** @var \Logeecom\Infrastructure\ORM\Interfaces\RepositoryInterface */
    protected $orderDetailsRepository;
    /** @var \Packlink\Repositories\QueueItemRepository */
    protected $queueItemRepository;
    /** @var QueueService */
    protected $queueService;
    /** @var \Logeecom\Infrastructure\ORM\Interfaces\RepositoryInterface */
    protected $shippingMethodMapRepository;
    /** @var \Logeecom\Infrastructure\ORM\Interfaces\RepositoryInterface */
    protected $shippingMethodRepository;
    /** @var \Packlink\BusinessLogic\Order\OrderService */
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
     * @return \Packlink\BusinessLogic\User\UserAccountService
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
     * @return \Packlink\BusinessLogic\Location\LocationService
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
     * @return \Packlink\BusinessLogic\Http\Proxy
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
     * @return \Logeecom\Infrastructure\ORM\Interfaces\RepositoryInterface
     *
     * @throws \Logeecom\Infrastructure\ORM\Exceptions\RepositoryNotRegisteredException
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
     * @throws \Logeecom\Infrastructure\ORM\Exceptions\RepositoryClassException
     * @throws \Logeecom\Infrastructure\ORM\Exceptions\RepositoryNotRegisteredException
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
     * @return \Logeecom\Infrastructure\TaskExecution\QueueService
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
     * @return \Logeecom\Infrastructure\ORM\Interfaces\RepositoryInterface
     *
     * @throws \Logeecom\Infrastructure\ORM\Exceptions\RepositoryNotRegisteredException
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
     * @return \Logeecom\Infrastructure\ORM\Interfaces\RepositoryInterface
     * @throws \Logeecom\Infrastructure\ORM\Exceptions\RepositoryNotRegisteredException
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
     * @return \Packlink\BusinessLogic\Order\OrderService
     */
    protected function getOrderService()
    {
        if ($this->orderService === null) {
            $this->orderService = ServiceRegister::getService(OrderService::CLASS_NAME);
        }

        return $this->orderService;
    }
}