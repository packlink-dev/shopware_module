<?php

use Packlink\Controllers\Common\CanInstantiateServices;
use Packlink\Utilities\Request;
use Packlink\Utilities\Response;

class Shopware_Controllers_Frontend_PacklinkDropoff extends Enlight_Controller_Action
{
    use CanInstantiateServices;

    /**
     * Sets selected dropoff location.
     *
     * @throws \Packlink\Exceptions\FailedToRetrieveCheckoutAddressException
     * @throws \Packlink\Exceptions\FailedToRetrieveDefaultUserAddressException
     * @throws \Exception
     */
    public function updateAction() {
        $payload = Request::getPostData();
        if (empty($payload['carrierId']) || empty($payload['dropOff'])) {
            $this->Response()->setStatusCode(400);
            $this->View()->assign(['response' => '']);

            return;
        }

        $currentCarrier = Shopware()->Session()->get('sDispatch');
        /** @noinspection TypeUnsafeComparisonInspection */
        if ($currentCarrier != $payload['carrierId']) {
            $this->Response()->setStatusCode(400);
            $this->View()->assign(['response' => '']);

            return;
        }

        $userId = Shopware()->Session()->get('sUserId');
        $shippingId = Shopware()->Session()->get('checkoutShippingAddressId');
        $shippingId = !empty($shippingId) ? (int)$shippingId : null;

        $currentAddress = $this->getCheckoutService()->getShippingAddress((int) $userId, $shippingId);

        Shopware()->Session()->offsetSet('plSession', Shopware()->Session()->get('sessionId'));
        Shopware()->Session()->offsetSet('plCarrier', $payload['carrierId']);
        Shopware()->Session()->offsetSet('plDropoff', $payload['dropOff']);
        Shopware()->Session()->offsetSet('plShippingAddress', $currentAddress);
        Shopware()->Session()->offsetUnset('plIsDropoff');
        Shopware()->Session()->offsetUnset('plIsSelected');

        $this->View()->assign(['response' => '']);
    }
}