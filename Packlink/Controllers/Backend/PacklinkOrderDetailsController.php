<?php

namespace Packlink\Controllers\Backend;

use Logeecom\Infrastructure\ORM\QueryFilter\Operators;
use Logeecom\Infrastructure\ORM\QueryFilter\QueryFilter;
use Logeecom\Infrastructure\ServiceRegister;
use Packlink\BusinessLogic\OrderShipmentDetails\OrderShipmentDetailsService;
use Packlink\Controllers\Common\CanInstantiateServices;

class PacklinkOrderDetailsController extends \Enlight_Controller_Action
{
    use CanInstantiateServices;

    /**
     * @var OrderShipmentDetailsService
     */
    private $orderShipmentDetailsService;

    /**
     * Returns an instance of order shipment details service.
     *
     * @return OrderShipmentDetailsService
     */
    protected function getOrderShipmentDetailsService()
    {
        if ($this->orderShipmentDetailsService === null) {
            $this->orderShipmentDetailsService = ServiceRegister::getService(OrderShipmentDetailsService::CLASS_NAME);
        }

        return $this->orderShipmentDetailsService;
    }

    /**
     * Retrieves task.
     *
     * @param $taskId
     *
     * @return \Logeecom\Infrastructure\TaskExecution\QueueItem
     *
     * @throws \Logeecom\Infrastructure\ORM\Exceptions\QueryFilterInvalidParamException
     * @throws \Logeecom\Infrastructure\ORM\Exceptions\RepositoryClassException
     * @throws \Logeecom\Infrastructure\ORM\Exceptions\RepositoryNotRegisteredException
     */
    protected function getTask($taskId)
    {
        $filter = new QueryFilter();
        $filter->where('id', Operators::EQUALS, $taskId);
        /** @var \Logeecom\Infrastructure\TaskExecution\QueueItem $item */
        $item = $this->getQueueItemRepository()->selectOne($filter);

        return $item;
    }
}