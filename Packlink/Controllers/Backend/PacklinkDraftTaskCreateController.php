<?php

use Logeecom\Infrastructure\ServiceRegister;
use Packlink\BusinessLogic\ShipmentDraft\ShipmentDraftService;
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
     * @throws \Logeecom\Infrastructure\ORM\Exceptions\RepositoryNotRegisteredException
     * @throws \Logeecom\Infrastructure\TaskExecution\Exceptions\QueueStorageUnavailableException
     * @throws \Packlink\BusinessLogic\ShipmentDraft\Exceptions\DraftTaskMapExists
     * @throws \Packlink\BusinessLogic\ShipmentDraft\Exceptions\DraftTaskMapNotFound
     * @throws \Logeecom\Infrastructure\ORM\Exceptions\QueryFilterInvalidParamException
     */
    public function createAction()
    {
        $payload = Request::getPostData();
        if (empty($payload['orderId'])) {
            Response::json([], 400);
        }

        /** @var ShipmentDraftService $shipmentDraftService */
        $shipmentDraftService = ServiceRegister::getService(ShipmentDraftService::CLASS_NAME);
        $shipmentDraftService->enqueueCreateShipmentDraftTask((string)$payload['orderId']);

        Response::json(['success' => true]);
    }
}