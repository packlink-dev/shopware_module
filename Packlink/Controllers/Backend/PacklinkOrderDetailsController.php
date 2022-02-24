<?php

namespace Packlink\Controllers\Backend;

use Packlink\Infrastructure\ORM\QueryFilter\Operators;
use Packlink\Infrastructure\ORM\QueryFilter\QueryFilter;
use Packlink\Controllers\Common\CanInstantiateServices;

class PacklinkOrderDetailsController extends \Enlight_Controller_Action
{
    use CanInstantiateServices;

    protected function return400()
    {
        $this->Response()->setStatusCode(400);
        $this->View()->assign('response', []);
    }

    /**
     * Retrieves order details.
     *
     * @param string $orderId
     *
     * @return \Packlink\BusinessLogic\OrderShipmentDetails\Models\OrderShipmentDetails | null
     *
     * @throws \Packlink\Infrastructure\ORM\Exceptions\QueryFilterInvalidParamException
     * @throws \Packlink\Infrastructure\ORM\Exceptions\RepositoryNotRegisteredException
     */
    protected function getOrderDetails($orderId)
    {
        $filter = new QueryFilter();
        $filter->where('orderId', Operators::EQUALS, $orderId);
        /** @var \Packlink\BusinessLogic\OrderShipmentDetails\Models\OrderShipmentDetails $details */
        $details = $this->getOrderDetailsRepository()->selectOne($filter);

        return $details;
    }

    /**
     * Retrieves task.
     *
     * @param $taskId
     *
     * @return \Packlink\Infrastructure\TaskExecution\QueueItem
     *
     * @throws \Packlink\Infrastructure\ORM\Exceptions\QueryFilterInvalidParamException
     * @throws \Packlink\Infrastructure\ORM\Exceptions\RepositoryClassException
     * @throws \Packlink\Infrastructure\ORM\Exceptions\RepositoryNotRegisteredException
     */
    protected function getTask($taskId)
    {
        $filter = new QueryFilter();
        $filter->where('id', Operators::EQUALS, $taskId);
        /** @var \Packlink\Infrastructure\TaskExecution\QueueItem $item */
        $item = $this->getQueueItemRepository()->selectOne($filter);

        return $item;
    }
}
