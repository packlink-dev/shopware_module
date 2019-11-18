<?php

namespace Packlink\Controllers\Backend;

use Logeecom\Infrastructure\ORM\QueryFilter\Operators;
use Logeecom\Infrastructure\ORM\QueryFilter\QueryFilter;
use Packlink\Controllers\Common\CanInstantiateServices;

class PacklinkOrderDetailsController extends \Enlight_Controller_Action
{
    use CanInstantiateServices;

    /**
     * Retrieves order details.
     *
     * @param int $orderId
     *
     * @return \Packlink\BusinessLogic\Order\Models\OrderShipmentDetails | null
     *
     * @throws \Logeecom\Infrastructure\ORM\Exceptions\QueryFilterInvalidParamException
     * @throws \Logeecom\Infrastructure\ORM\Exceptions\RepositoryNotRegisteredException
     */
    protected function getOrderDetails($orderId)
    {
        $filter = new QueryFilter();
        $filter->where('orderId', Operators::EQUALS, $orderId);
        /** @var \Packlink\BusinessLogic\Order\Models\OrderShipmentDetails $details */
        $details = $this->getOrderDetailsRepository()->selectOne($filter);

        return $details;
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