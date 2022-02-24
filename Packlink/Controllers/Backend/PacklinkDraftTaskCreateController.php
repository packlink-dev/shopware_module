<?php

use Packlink\Infrastructure\ServiceRegister;
use Packlink\BusinessLogic\ShipmentDraft\ShipmentDraftService;
use Packlink\Controllers\Backend\PacklinkOrderDetailsController;
use Packlink\Controllers\Common\CanInstantiateServices;
use Packlink\Utilities\Request;

class Shopware_Controllers_Backend_PacklinkDraftTaskCreateController extends PacklinkOrderDetailsController
{
    use CanInstantiateServices;

    /**
     * Creates send draft if necessary task.
     *
     * @throws \Packlink\Infrastructure\ORM\Exceptions\RepositoryNotRegisteredException
     * @throws \Packlink\Infrastructure\TaskExecution\Exceptions\QueueStorageUnavailableException
     * @throws \Packlink\BusinessLogic\ShipmentDraft\Exceptions\DraftTaskMapExists
     * @throws \Packlink\BusinessLogic\ShipmentDraft\Exceptions\DraftTaskMapNotFound
     * @throws \Packlink\Infrastructure\ORM\Exceptions\QueryFilterInvalidParamException
     */
    public function createAction()
    {
        $payload = Request::getPostData();
        if (empty($payload['orderId'])) {
            $this->return400();

            return;
        }

        /** @var ShipmentDraftService $shipmentDraftService */
        $shipmentDraftService = ServiceRegister::getService(ShipmentDraftService::CLASS_NAME);
        $shipmentDraftService->enqueueCreateShipmentDraftTask((string)$payload['orderId']);

        $this->View()->assign('response', ['success' => true]);
    }
}