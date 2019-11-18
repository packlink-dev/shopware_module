<?php

use Packlink\Controllers\Common\CanInstantiateServices;
use Packlink\Utilities\Response;

class Shopware_Controllers_Frontend_PacklinkLocations extends Enlight_Controller_Action
{
    use CanInstantiateServices;

    /**
     * Retrieves available dropoff locations.
     */
    public function listAction()
    {
        $methodId = $this->Request()->get('methodId');
        $userId = Shopware()->Session()->get('sUserId');
        if (empty($methodId) || empty($userId)) {
            Response::json();
        }

        $shippingId = Shopware()->Session()->get('checkoutShippingAddressId');
        $shippingId = !empty($shippingId) ? (int)$shippingId : null;
        try {
            $address = $this->getCheckoutService()->getShippingAddress((int)$userId, $shippingId);
        } catch (Exception $e) {
            Response::json();
        }

        $locations = $this->getLocationService()->getLocations(
            (int)$methodId,
            $address['countryCode'],
            $address['postalCode']
        );

        Response::json($locations);
    }
}