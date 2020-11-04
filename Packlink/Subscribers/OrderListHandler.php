<?php

namespace Packlink\Subscribers;

use Enlight\Event\SubscriberInterface;
use Enlight_Hook_HookArgs;
use Packlink\Core\Infrastructure\Configuration\Configuration;
use Packlink\Core\Infrastructure\ServiceRegister;
use Packlink\Core\Infrastructure\TaskExecution\QueueItem;
use Packlink\Core\BusinessLogic\Order\OrderService;
use Packlink\Core\BusinessLogic\OrderShipmentDetails\OrderShipmentDetailsService;
use Packlink\Core\BusinessLogic\ShipmentDraft\ShipmentDraftService;

class OrderListHandler implements SubscriberInterface
{
    /**
     * @var \Packlink\Services\BusinessLogic\ConfigurationService
     */
    protected $configService;
    /**
     * @var \Packlink\Core\BusinessLogic\Order\OrderService
     */
    protected $orderService;

    /**
     * @inheritDoc
     */
    public static function getSubscribedEvents()
    {
        return [
            'Shopware_Controllers_Backend_Order::getList::after' => 'extendOrderList',
        ];
    }

    /**
     * Extends order list with additional data.
     *
     * @param \Enlight_Hook_HookArgs $args
     *
     * @throws \Packlink\Core\Infrastructure\ORM\Exceptions\QueryFilterInvalidParamException
     * @throws \Packlink\Core\Infrastructure\ORM\Exceptions\RepositoryNotRegisteredException
     */
    public function extendOrderList(Enlight_Hook_HookArgs $args)
    {
        if (!$this->isLoggedIn()) {
            return;
        }

        $return = $args->getReturn();

        /** @var \Packlink\Core\BusinessLogic\OrderShipmentDetails\OrderShipmentDetailsService $orderShipmentDetailsService */
        $orderShipmentDetailsService = ServiceRegister::getService(OrderShipmentDetailsService::CLASS_NAME);
        /** @var ShipmentDraftService $draftService */
        $draftService = ServiceRegister::getService(ShipmentDraftService::CLASS_NAME);

        foreach ($return['data'] as $index => $order) {
            $orderDetails = $orderShipmentDetailsService->getDetailsByOrderId((string)$order['id']);
            $draftStatus = $draftService->getDraftStatus((string)$order['id']);
            $draftCreated = $draftStatus->status === QueueItem::COMPLETED && $orderDetails;
            $return['data'][$index]['plDraftStatus'] = $draftStatus->status;
            $return['data'][$index]['plReferenceUrl'] = $draftCreated ? $orderDetails->getShipmentUrl() : '#';
            $return['data'][$index]['plIsDeleted'] = $draftCreated ? $orderDetails->isDeleted() : false;

            if ($orderDetails !== null && $orderDetails->getReference()) {
                $orderService = $this->getOrderService();
                $isLabelsAvailable = $orderService->isReadyToFetchShipmentLabels($orderDetails->getStatus());

                if ($isLabelsAvailable) {
                    $labels = $orderDetails->getShipmentLabels();

                    $return['data'][$index]['plHasLabel'] = true;
                    $return['data'][$index]['plIsLabelPrinted'] = !empty($labels) && $labels[0]->isPrinted();
                }
            }
        }

        $args->setReturn($return);
    }

    /**
     * Retrieves user country. Fallback is de.
     *
     * @return string
     */
    protected function getUserCountry()
    {
        $userAccount = $this->getConfigService()->getUserInfo();

        return strtolower($userAccount ? $userAccount->country : 'un');
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

    /**
     * Retrieves config service.
     *
     * @return \Packlink\Services\BusinessLogic\ConfigurationService
     */
    protected function getConfigService()
    {
        if ($this->configService === null) {
            $this->configService = ServiceRegister::getService(Configuration::CLASS_NAME);
        }

        return $this->configService;
    }

    /**
     * Retrieves order service.
     *
     * @return \Packlink\Core\BusinessLogic\Order\OrderService
     */
    protected function getOrderService()
    {
        if ($this->orderService === null) {
            $this->orderService = ServiceRegister::getService(OrderService::CLASS_NAME);
        }

        return $this->orderService;
    }
}