<?php

use Logeecom\Infrastructure\ServiceRegister;
use Logeecom\Infrastructure\TaskExecution\QueueItem;
use Packlink\BusinessLogic\ShipmentDraft\Objects\ShipmentDraftStatus;
use Packlink\BusinessLogic\ShipmentDraft\ShipmentDraftService;
use Packlink\Controllers\Backend\PacklinkOrderDetailsController;
use Packlink\Utilities\Response;

class Shopware_Controllers_Backend_PacklinkDraftTaskStatusController extends PacklinkOrderDetailsController
{
    const NOT_LOGGED_IN_STATUS = 'not_logged_in';

    /**
     * Retrieves send draft task status for particular order.
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

        switch ($draftStatus->status) {
            case ShipmentDraftStatus::NOT_QUEUED:
                Response::json(['status' => ShipmentDraftStatus::NOT_QUEUED]);
            case QueueItem::FAILED:
                Response::json(['status' => QueueItem::FAILED]);
            case QueueItem::COMPLETED:
                Response::json(['status' => QueueItem::COMPLETED]);
            default:
                Response::json(['status' => QueueItem::IN_PROGRESS]);
        }
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
