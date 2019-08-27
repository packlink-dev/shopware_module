<?php

use Packlink\Controllers\Common\CanInstantiateServices;
use Packlink\Utilities\Response;
use Shopware\Components\CSRFWhitelistAware;

class Shopware_Controllers_Frontend_PacklinkLocations extends Enlight_Controller_Action implements CSRFWhitelistAware
{
    use CanInstantiateServices;

    /**
     * Returns a list with actions which should not be validated for CSRF protection
     *
     * @return string[]
     */
    public function getWhitelistedCSRFActions()
    {
        return ['list'];
    }

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