<?php

namespace Packlink\Subscribers;

use Doctrine\Common\EventSubscriber;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\ORM\Events;
use Logeecom\Infrastructure\ServiceRegister;
use Packlink\BusinessLogic\Configuration;
use Packlink\BusinessLogic\OrderShipmentDetails\Models\OrderShipmentDetails;
use Packlink\BusinessLogic\ShipmentDraft\ShipmentDraftService;
use Shopware\Models\Order\Order;
use Shopware\Models\Order\Status;

class OrderModelEventHandler implements EventSubscriber
{
    /**
     * @var \Packlink\Services\BusinessLogic\ConfigurationService
     */
    protected $configService;
    /**
     * @var \Packlink\BusinessLogic\ShipmentDraft\ShipmentDraftService
     */
    protected $shipmentDraftService;
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
     * @throws \Packlink\BusinessLogic\ShipmentDraft\Exceptions\DraftTaskMapExists
     * @throws \Packlink\BusinessLogic\ShipmentDraft\Exceptions\DraftTaskMapNotFound
     */
    public function postPersist(LifecycleEventArgs $args)
    {
        $model = $args->getEntity();
        if (!$this->shouldHandle($model)) {
            return;
        }

        $this->shipmentDraftService->enqueueCreateShipmentDraftTask($model->getId());
    }

    /**
     * Handles post update event.
     *
     * @param \Doctrine\ORM\Event\LifecycleEventArgs $args
     *
     * @throws \Logeecom\Infrastructure\ORM\Exceptions\QueryFilterInvalidParamException
     * @throws \Logeecom\Infrastructure\ORM\Exceptions\RepositoryNotRegisteredException
     * @throws \Logeecom\Infrastructure\TaskExecution\Exceptions\QueueStorageUnavailableException
     * @throws \Packlink\BusinessLogic\ShipmentDraft\Exceptions\DraftTaskMapExists
     * @throws \Packlink\BusinessLogic\ShipmentDraft\Exceptions\DraftTaskMapNotFound
     */
    public function postUpdate(LifecycleEventArgs $args)
    {
        $model = $args->getEntity();
        if (!$this->shouldHandle($model)) {
            return;
        }

        $this->shipmentDraftService->enqueueCreateShipmentDraftTask($model->getId());
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
     * Retrieves shipment draft service.
     *
     * @return \Packlink\BusinessLogic\ShipmentDraft\ShipmentDraftService
     */
    protected function getShipmentDraftService()
    {
        if ($this->shipmentDraftService === null) {
            $this->shipmentDraftService = ServiceRegister::getService(ShipmentDraftService::CLASS_NAME);
        }

        return $this->shipmentDraftService;
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
        /** @var \Packlink\BusinessLogic\OrderShipmentDetails\OrderShipmentDetailsService $orderShipmentDetailsService */
        $orderShipmentDetailsService = ServiceRegister::getService(OrderShipmentDetails::CLASS_NAME);
        $details = $orderShipmentDetailsService->getDetailsByOrderId($id);

        return $details !== null;
    }
}