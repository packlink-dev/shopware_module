<?php

use Packlink\Core\Infrastructure\ServiceRegister;
use Packlink\Core\BusinessLogic\ShipmentDraft\ShipmentDraftService;
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
     * @throws \Packlink\Core\Infrastructure\ORM\Exceptions\RepositoryNotRegisteredException
     * @throws \Packlink\Core\Infrastructure\TaskExecution\Exceptions\QueueStorageUnavailableException
     * @throws \Packlink\Core\BusinessLogic\ShipmentDraft\Exceptions\DraftTaskMapExists
     * @throws \Packlink\Core\BusinessLogic\ShipmentDraft\Exceptions\DraftTaskMapNotFound
     * @throws \Packlink\Core\Infrastructure\ORM\Exceptions\QueryFilterInvalidParamException
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