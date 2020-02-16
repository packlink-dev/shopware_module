<?php

use Logeecom\Infrastructure\Logger\Logger;
use Logeecom\Infrastructure\ORM\QueryFilter\Operators;
use Logeecom\Infrastructure\ORM\QueryFilter\QueryFilter;
use Packlink\Controllers\Backend\PacklinkOrderDetailsController;
use Packlink\Utilities\Reference;
use Packlink\Utilities\Response;
use Packlink\Utilities\Translation;

class Shopware_Controllers_Backend_PacklinkDraftDetailsController extends PacklinkOrderDetailsController
{
    /**
     * Retrieves order details.
     *
     * @throws \Logeecom\Infrastructure\ORM\Exceptions\QueryFilterInvalidParamException
     * @throws \Logeecom\Infrastructure\ORM\Exceptions\RepositoryNotRegisteredException
     * @throws \Exception
     */
    public function indexAction()
    {
        $orderId = $this->Request()->get('orderId');

        if (empty($orderId)) {
            Response::json([], 400);
        }

        /** @var \Shopware\Models\Order\Order $order */
        $order = $this->getShopwareOrderRepository()->find((int)$orderId);
        $orderDetails = $this->getOrderDetails($orderId);
        if ($order === null || $orderDetails === null) {
            Response::json([], 400);
        }

        /** @noinspection PhpUndefinedVariableInspection */
        $details = [
            'orderCost' => $order->getInvoiceShipping(),
            'cost' => $orderDetails->getShippingCost(),
            'status' => Translation::get('shipment/packlink/status/' . $orderDetails->getStatus()),
            'reference' => $orderDetails->getReference(),
            'isLabelsAvailable' => false,
            'isDeleted' => $orderDetails->isDeleted(),
        ];

        if ($this->getOrderService()->isReadyToFetchShipmentLabels($orderDetails->getStatus())) {
            $details['isLabelsAvailable'] = true;
            $labels = $orderDetails->getShipmentLabels();
            $details['isLabelsPrinted'] = !empty($labels) && $orderDetails->getShipmentLabels()[0]->isPrinted();
        }

        if (!empty($orderDetails->getCarrierTrackingNumbers())) {
            $details['trackingNumbers'] = implode(', ', $orderDetails->getCarrierTrackingNumbers());
            $details['trackingUrl'] = $orderDetails->getCarrierTrackingUrl();
        }

        $country = $this->getUserCountry();

        if ($details['reference']) {
            $details['referenceUrl'] = Reference::getUrl($country, $details['reference']);
        }

        try {
            $dispatch = $order->getDispatch();
            $shippingMethod = $this->getShippingMethod($dispatch->getId());

            $details = array_merge(
                $details,
                [
                    'shippingMethod' => $shippingMethod ? $shippingMethod->getTitle() : '',
                    'carrier' => $dispatch->getName(),
                    'logo' => $shippingMethod ? $shippingMethod->getLogoUrl() : '',
                ]
            );
        } catch (Exception $e) {
            Logger::logWarning("Failed to retrieve dispatch because: {$e->getMessage()}");
        }

        /** @noinspection PhpUndefinedVariableInspection */
        Response::json($details);
    }

    /**
     * Retrieves shipping method by provided ID.
     *
     * @param $shopwareCarrierId
     *
     * @return \Packlink\BusinessLogic\ShippingMethod\Models\ShippingMethod
     *
     * @throws \Logeecom\Infrastructure\ORM\Exceptions\QueryFilterInvalidParamException
     * @throws \Logeecom\Infrastructure\ORM\Exceptions\RepositoryNotRegisteredException
     */
    protected function getShippingMethod($shopwareCarrierId)
    {
        $filter = new QueryFilter();
        $filter->where('shopwareCarrierId', Operators::EQUALS, $shopwareCarrierId);
        /** @var \Packlink\Entities\ShippingMethodMap $map */
        $map = $this->getShippingMethodMapRepository()->selectOne($filter);

        if ($map === null) {
            return null;
        }

        $filter = new QueryFilter();
        $filter->where('id', Operators::EQUALS, $map->shippingMethodId);

        /** @var \Packlink\BusinessLogic\ShippingMethod\Models\ShippingMethod $method */
        $method = $this->getShippingMethodRepository()->selectOne($filter);

        return $method;
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
}