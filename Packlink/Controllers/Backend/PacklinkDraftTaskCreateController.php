<?php

use Packlink\BusinessLogic\Controllers\DraftController;
use Packlink\BusinessLogic\Tasks\SendDraftTask;
use Packlink\Controllers\Backend\PacklinkOrderDetailsController;
use Packlink\Controllers\Common\CanInstantiateServices;
use Packlink\Utilities\Request;
use Packlink\Utilities\Response;

class Shopware_Controllers_Backend_PacklinkDraftTaskCreateController extends PacklinkOrderDetailsController
{
    use CanInstantiateServices;

    /**
     * Creates send draft if necessary task.
     *
     * @throws \Logeecom\Infrastructure\ORM\Exceptions\QueryFilterInvalidParamException
     * @throws \Logeecom\Infrastructure\ORM\Exceptions\RepositoryClassException
     * @throws \Logeecom\Infrastructure\ORM\Exceptions\RepositoryNotRegisteredException
     * @throws \Logeecom\Infrastructure\TaskExecution\Exceptions\QueueStorageUnavailableException
     */
    public function createAction()
    {
        $payload = Request::getPostData();
        if (empty($payload['orderId'])) {
            Response::json([], 400);
        }

        if (($orderDetails = $this->getOrderDetails((int)$payload['orderId'])) === null) {
            DraftController::createDraft((int)$payload['orderId']);
        } else {
            if ($orderDetails->getTaskId() === null || ($task = $this->getTask($orderDetails->getTaskId())) === null) {
                $draftTask = new SendDraftTask((int)$payload['orderId']);
                $this->getQueueService()->enqueue($this->getConfigService()->getDefaultQueueName(), $draftTask);
                if ($draftTask->getExecutionId() && $orderDetails = $this->getOrderDetails($payload['orderId'])) {
                    $orderDetails->setTaskId($draftTask->getExecutionId());
                    $this->getOrderDetailsRepository()->update($orderDetails);
                }
            }
        }

        Response::json(['success' => true]);
    }
}