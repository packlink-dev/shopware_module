<?php

namespace Packlink\Subscribers;

use Doctrine\Common\EventSubscriber;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\ORM\Events;
use Logeecom\Infrastructure\ORM\QueryFilter\Operators;
use Logeecom\Infrastructure\ORM\QueryFilter\QueryFilter;
use Logeecom\Infrastructure\ORM\RepositoryRegistry;
use Logeecom\Infrastructure\ServiceRegister;
use Packlink\Bootstrap\Bootstrap;
use Packlink\BusinessLogic\Configuration;
use Packlink\BusinessLogic\Controllers\DraftController;
use Packlink\BusinessLogic\Order\Models\OrderShipmentDetails;
use Shopware\Models\Order\Order;
use Shopware\Models\Order\Status;

class OrderModelEventHandler implements EventSubscriber
{
    /**
     * @var \Packlink\Services\BusinessLogic\ConfigurationService
     */
    protected $configService;
    /**
     * @var \Logeecom\Infrastructure\ORM\Interfaces\RepositoryInterface
     */
    protected $orderDetailsRepository;

    /**
     * Returns an array of events this subscriber wants to listen to.
     *
     * @return array
     */
    public function getSubscribedEvents()
    {
        return [
            Events::postPersist,
            Events::postUpdate,
        ];
    }

    /**
     * Handles post persist event.
     *
     * @param \Doctrine\ORM\Event\LifecycleEventArgs $args
     *
     * @throws \Logeecom\Infrastructure\ORM\Exceptions\QueryFilterInvalidParamException
     * @throws \Logeecom\Infrastructure\ORM\Exceptions\RepositoryNotRegisteredException
     * @throws \Logeecom\Infrastructure\TaskExecution\Exceptions\QueueStorageUnavailableException
     */
    public function postPersist(LifecycleEventArgs $args)
    {
        Bootstrap::init();

        $model = $args->getEntity();
        if (!$this->shouldHandle($model)) {
            return;
        }

        DraftController::createDraft($model->getId());
    }

    /**
     * Handles post update event.
     *
     * @param \Doctrine\ORM\Event\LifecycleEventArgs $args
     *
     * @throws \Logeecom\Infrastructure\ORM\Exceptions\QueryFilterInvalidParamException
     * @throws \Logeecom\Infrastructure\ORM\Exceptions\RepositoryNotRegisteredException
     * @throws \Logeecom\Infrastructure\TaskExecution\Exceptions\QueueStorageUnavailableException
     */
    public function postUpdate(LifecycleEventArgs $args)
    {
        Bootstrap::init();

        $model = $args->getEntity();
        if (!$this->shouldHandle($model)) {
            return;
        }

        DraftController::createDraft($model->getId());
    }

    /**
     * Checks whether event should be handled.
     *
     * @param mixed $model
     *
     * @return bool
     *
     * @throws \Logeecom\Infrastructure\ORM\Exceptions\QueryFilterInvalidParamException
     * @throws \Logeecom\Infrastructure\ORM\Exceptions\RepositoryNotRegisteredException
     */
    protected function shouldHandle($model)
    {
        return $model instanceof Order
            && ($model->getOrderStatus()->getId() === Status::ORDER_STATE_READY_FOR_DELIVERY)
            && $this->isLoggedIn()
            && !$this->isOrderDetailsCreated((int)$model->getId());
    }

    /**
     * Checks if user is logged in.
     *
     * @return bool
     */
    protected function isLoggedIn()
    {
        $authToken = $this->getConfigService()->getAuthorizationToken();

        return !empty($authToken);
    }

    /**
     * Retrieves config service.
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
     * Checks whether order draft has been created.
     *
     * @param $id
     *
     * @return bool
     *
     * @throws \Logeecom\Infrastructure\ORM\Exceptions\QueryFilterInvalidParamException
     * @throws \Logeecom\Infrastructure\ORM\Exceptions\RepositoryNotRegisteredException
     */
    protected function isOrderDetailsCreated($id)
    {
        $filter = new QueryFilter();
        $filter->where('orderId', Operators::EQUALS, $id);
        $details = $this->getOrderDetailsRepository()->selectOne($filter);

        return $details !== null;
    }

    /**
     * Retrieves order details repository.
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

}