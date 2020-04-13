<?php

namespace Packlink\Subscribers;

use Enlight\Event\SubscriberInterface;
use Enlight_Hook_HookArgs;
use Logeecom\Infrastructure\Configuration\Configuration;
use Logeecom\Infrastructure\ServiceRegister;
use Packlink\BusinessLogic\Order\OrderService;
use Packlink\BusinessLogic\OrderShipmentDetails\OrderShipmentDetailsService;

class OrderListHandler implements SubscriberInterface
{
    /**
     * @var \Packlink\Services\BusinessLogic\ConfigurationService
     */
    protected $configService;
    /**
     * @var \Packlink\BusinessLogic\Order\OrderService
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
     * @throws \Logeecom\Infrastructure\ORM\Exceptions\QueryFilterInvalidParamException
     * @throws \Logeecom\Infrastructure\ORM\Exceptions\RepositoryNotRegisteredException
     */
    public function extendOrderList(Enlight_Hook_HookArgs $args)
    {
        if (!$this->isLoggedIn()) {
            return;
        }

        $return = $args->getReturn();

        /** @var \Packlink\BusinessLogic\OrderShipmentDetails\OrderShipmentDetailsService $orderShipmentDetailsService */
        $orderShipmentDetailsService = ServiceRegister::getService(OrderShipmentDetailsService::CLASS_NAME);

        foreach ($return['data'] as $index => $order) {
            $orderDetails = $orderShipmentDetailsService->getDetailsByOrderId((string)$order['id']);
            if ($orderDetails !== null && $orderDetails->getReference()) {
                $return['data'][$index]['plReferenceUrl'] = $orderDetails->getShipmentUrl();
                $return['data'][$index]['plIsDeleted'] = $orderDetails->isDeleted();

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
     * @return \Packlink\BusinessLogic\Order\OrderService
     */
    protected function getOrderService()
    {
        if ($this->orderService === null) {
            $this->orderService = ServiceRegister::getService(OrderService::CLASS_NAME);
        }

        return $this->orderService;
    }
}