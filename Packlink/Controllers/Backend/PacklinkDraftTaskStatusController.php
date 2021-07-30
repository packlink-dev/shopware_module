<?php

use Packlink\Infrastructure\ServiceRegister;
use Packlink\Infrastructure\TaskExecution\QueueItem;
use Packlink\BusinessLogic\OrderShipmentDetails\Exceptions\OrderShipmentDetailsNotFound;
use Packlink\BusinessLogic\OrderShipmentDetails\OrderShipmentDetailsService;
use Packlink\BusinessLogic\ShipmentDraft\ShipmentDraftService;
use Packlink\Controllers\Backend\PacklinkOrderDetailsController;

class Shopware_Controllers_Backend_PacklinkDraftTaskStatusController extends PacklinkOrderDetailsController
{
    const NOT_LOGGED_IN_STATUS = 'not_logged_in';

    /**
     * Retrieves send draft task status for particular order.
     *
     * @throws \Packlink\BusinessLogic\OrderShipmentDetails\Exceptions\OrderShipmentDetailsNotFound
     */
    public function indexAction()
    {
        if (!$this->isLoggedIn()) {
            $this->View()->assign('response', ['status' => static::NOT_LOGGED_IN_STATUS]);
        }

        $orderId = $this->Request()->get('orderId');
        if (empty($orderId)) {
            $this->return400();

            return;
        }

        /** @var ShipmentDraftService $shipmentDraftService */
        $shipmentDraftService = ServiceRegister::getService(ShipmentDraftService::CLASS_NAME);
        $draftStatus = $shipmentDraftService->getDraftStatus($orderId);
        if ($draftStatus->status === QueueItem::QUEUED) {
            $this->View()->assign('response', ['status' => QueueItem::IN_PROGRESS]);
        }

        $response = $draftStatus->toArray();

        if ($draftStatus->status === QueueItem::COMPLETED) {
            /** @var OrderShipmentDetailsService $orderShipmentDetailsService */
            $orderShipmentDetailsService = ServiceRegister::getService(OrderShipmentDetailsService::CLASS_NAME);
            $shipmentDetails = $orderShipmentDetailsService->getDetailsByOrderId($orderId);

            if ($shipmentDetails === null) {
                throw new OrderShipmentDetailsNotFound('Order details not found.');
            }

            $response['shipmentUrl'] = $shipmentDetails->getShipmentUrl();
        }

        $this->View()->assign('response', $response);
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
