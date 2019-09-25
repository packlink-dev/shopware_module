<?php

use Logeecom\Infrastructure\TaskExecution\QueueItem;
use Packlink\Controllers\Backend\PacklinkOrderDetailsController;
use Packlink\Utilities\Response;

class Shopware_Controllers_Backend_PacklinkDraftTaskStatusController extends PacklinkOrderDetailsController
{
    /**
     * Retrieves send draft task status for particular order.
     *
     * @throws \Logeecom\Infrastructure\ORM\Exceptions\QueryFilterInvalidParamException
     * @throws \Logeecom\Infrastructure\ORM\Exceptions\RepositoryClassException
     * @throws \Logeecom\Infrastructure\ORM\Exceptions\RepositoryNotRegisteredException
     */
    public function indexAction()
    {
        if (!$this->isLoggedIn()) {
            Response::json(['status' => 'not_logged_in']);
        }

        $orderId = $this->Request()->get('orderId');
        if (empty($orderId)) {
            Response::json([], 400);
        }

        if (($orderDetails = $this->getOrderDetails((int)$orderId)) === null ||
            $orderDetails->getTaskId() === null ||
            ($task = $this->getTask($orderDetails->getTaskId())) === null
        ) {
            Response::json(['status' => 'not_created']);
        }

        /** @noinspection PhpUndefinedVariableInspection */
        switch ($task->getStatus()) {
            case QueueItem::CREATED:
            case QueueItem::QUEUED:
            case QueueItem::IN_PROGRESS:
                $status = 'in_progress';
                break;
            case QueueItem::COMPLETED:
                $status = 'completed';
                break;
            default:
                $status = 'failed';
        }

        Response::json(['status' => $status]);
    }

    /**
     * Checks whether user is logged in.
     *
     * @return bool
     */
    protected function isLoggedIn()
    {
        $authToken = $this->getConfigService()->getAuthorizationToken();

        return !empty($authToken);
    }

}