<?php

namespace Packlink\Controllers\Backend;

use Packlink\Core\Infrastructure\ORM\QueryFilter\Operators;
use Packlink\Core\Infrastructure\ORM\QueryFilter\QueryFilter;
use Packlink\Controllers\Common\CanInstantiateServices;

class PacklinkOrderDetailsController extends \Enlight_Controller_Action
{
    use CanInstantiateServices;

    /**
     * Retrieves order details.
     *
     * @param string $orderId
     *
     * @return \Packlink\Core\BusinessLogic\OrderShipmentDetails\Models\OrderShipmentDetails | null
     *
     * @throws \Packlink\Core\Infrastructure\ORM\Exceptions\QueryFilterInvalidParamException
     * @throws \Packlink\Core\Infrastructure\ORM\Exceptions\RepositoryNotRegisteredException
     */
    protected function getOrderDetails($orderId)
    {
        $filter = new QueryFilter();
        $filter->where('orderId', Operators::EQUALS, $orderId);
        /** @var \Packlink\Core\BusinessLogic\OrderShipmentDetails\Models\OrderShipmentDetails $details */
        $details = $this->getOrderDetailsRepository()->selectOne($filter);

        return $details;
    }

    /**
     * Retrieves task.
     *
     * @param $taskId
     *
     * @return \Packlink\Core\Infrastructure\TaskExecution\QueueItem
     *
     * @throws \Packlink\Core\Infrastructure\ORM\Exceptions\QueryFilterInvalidParamException
     * @throws \Packlink\Core\Infrastructure\ORM\Exceptions\RepositoryClassException
     * @throws \Packlink\Core\Infrastructure\ORM\Exceptions\RepositoryNotRegisteredException
     */
    protected function getTask($taskId)
    {
        $filter = new QueryFilter();
        $filter->where('id', Operators::EQUALS, $taskId);
        /** @var \Packlink\Core\Infrastructure\TaskExecution\QueueItem $item */
        $item = $this->getQueueItemRepository()->selectOne($filter);

        return $item;
    }
}
