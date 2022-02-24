<?php

use Packlink\Controllers\Common\CanInstantiateServices;

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
            $this->View()->assign([]);

            return;
        }

        $shippingId = Shopware()->Session()->get('checkoutShippingAddressId');
        $shippingId = !empty($shippingId) ? (int)$shippingId : null;
        try {
            $address = $this->getCheckoutService()->getShippingAddress((int)$userId, $shippingId);
        } catch (Exception $e) {
            $this->View()->assign([]);

            return;
        }

        $locations = $this->getLocationService()->getLocations(
            (int)$methodId,
            $address['countryCode'],
            $address['postalCode']
        );

        $this->View()->assign('response', $locations);
    }
}