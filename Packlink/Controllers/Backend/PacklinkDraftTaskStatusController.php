<?php

use Logeecom\Infrastructure\ServiceRegister;
use Logeecom\Infrastructure\TaskExecution\QueueItem;
use Packlink\BusinessLogic\OrderShipmentDetails\Exceptions\OrderShipmentDetailsNotFound;
use Packlink\BusinessLogic\OrderShipmentDetails\OrderShipmentDetailsService;
use Packlink\BusinessLogic\ShipmentDraft\ShipmentDraftService;
use Packlink\Controllers\Backend\PacklinkOrderDetailsController;
use Packlink\Utilities\Response;

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
            Response::json(['status' => static::NOT_LOGGED_IN_STATUS]);
        }

        $orderId = $this->Request()->get('orderId');
        if (empty($orderId)) {
            Response::json([], 400);
        }

        /** @var ShipmentDraftService $shipmentDraftService */
        $shipmentDraftService = ServiceRegister::getService(ShipmentDraftService::CLASS_NAME);
        $draftStatus = $shipmentDraftService->getDraftStatus($orderId);
        if ($draftStatus->status === QueueItem::QUEUED) {
            Response::json(['status' => QueueItem::IN_PROGRESS]);
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

        Response::json($response);
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
