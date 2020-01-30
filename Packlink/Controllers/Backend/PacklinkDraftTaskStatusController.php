<?php

use Logeecom\Infrastructure\ServiceRegister;
use Logeecom\Infrastructure\TaskExecution\QueueItem;
use Packlink\BusinessLogic\ShipmentDraft\ShipmentDraftService;
use Packlink\Controllers\Backend\PacklinkOrderDetailsController;
use Packlink\Utilities\Response;

class Shopware_Controllers_Backend_PacklinkDraftTaskStatusController extends PacklinkOrderDetailsController
{
    const NOT_LOGGED_IN_STATUS = 'not_logged_in';
    const DRAFT_CREATION_IN_PROGRESS_STATUSES = [
      QueueItem::QUEUED,
      QueueItem::IN_PROGRESS,
    ];

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

        if (in_array($draftStatus->status, self::DRAFT_CREATION_IN_PROGRESS_STATUSES)) {
            Response::json(['status' => QueueItem::IN_PROGRESS]);
        }

        Response::json(['status' => $draftStatus->status]);
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
