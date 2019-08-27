<?php

namespace Packlink\Subscribers;

use Enlight\Event\SubscriberInterface;
use Enlight_Controller_ActionEventArgs;
use Exception;
use Logeecom\Infrastructure\ServiceRegister;
use Packlink\Bootstrap\Bootstrap;
use Packlink\BusinessLogic\Configuration;
use Packlink\Services\BusinessLogic\CheckoutService;
use Packlink\Services\BusinessLogic\DropoffService;

class DropoffHandler implements SubscriberInterface
{
    /** @var \Packlink\Services\BusinessLogic\ConfigurationService */
    protected $configService;
    /**
     * @var \Packlink\Services\BusinessLogic\DropoffService
     */
    protected $dropoffService;
    /**
     * @var \Packlink\Services\BusinessLogic\CheckoutService
     */
    protected $checkoutService;

    /**
     * @inheritDoc
     */
    public static function getSubscribedEvents()
    {
        return [
            'Enlight_Controller_Action_PostDispatch_Frontend_Checkout' => 'onCheckoutPostDispatch',
        ];
    }

    /**
     * Handles post dispatch event for Frontend Checkout controller.
     *
     * @param \Enlight_Controller_ActionEventArgs $args
     *
     * @throws \Logeecom\Infrastructure\ORM\Exceptions\QueryFilterInvalidParamException
     * @throws \Logeecom\Infrastructure\ORM\Exceptions\RepositoryNotRegisteredException
     */
    public function onCheckoutPostDispatch(Enlight_Controller_ActionEventArgs $args)
    {
        Bootstrap::init();

        $request = $args->getRequest();

        $isLoggedIn = $this->isLoggedIn();
        if (!$isLoggedIn || $request->getActionName() !== 'shippingPayment') {
            return;
        }

        $data = [
            'plIsLoggedIn' => $isLoggedIn,
        ];

        if ($isLoggedIn) {
            $data['plConfig'] = json_encode($this->getViewData());
        }

        $args->getSubject()->View()->assign($data);
    }

    /**
     * Retrieves view data.
     *
     * @return array
     *
     * @throws \Logeecom\Infrastructure\ORM\Exceptions\QueryFilterInvalidParamException
     * @throws \Logeecom\Infrastructure\ORM\Exceptions\RepositoryNotRegisteredException
     */
    protected function getViewData()
    {
        $data['dropOffs'] = $this->getDropoffService()->getDropoffCarriers();
        $data['dropOff'] = $this->getSelectedDropoff();

        return $data;
    }

    /**
     * Retrieves selected dropoff.
     *
     * @return array
     */
    protected function getSelectedDropoff()
    {
        $data['isSelected'] = false;

        $sessionId = Shopware()->Session()->get('sessionId');
        $currentCarrier = Shopware()->Session()->get('sDispatch');
        $userId = Shopware()->Session()->get('sUserId');
        $shippingId = Shopware()->Session()->get('checkoutShippingAddressId');
        $shippingId = !empty($shippingId) ? (int)$shippingId : null;
        $plSession = Shopware()->Session()->get('plSession');
        $plCarrier = Shopware()->Session()->get('plCarrier');
        $plDropoff = Shopware()->Session()->get('plDropoff');
        $plAddress = Shopware()->Session()->get('plShippingAddress');
        try {
            $shippingAddress = $this->getCheckoutService()->getShippingAddress($userId, $shippingId);
        } catch (Exception $e) {
            $this->invalidateSelectedDropoff();

            return $data;
        }

        /** @noinspection TypeUnsafeComparisonInspection */
        if (empty($plSession) ||
            empty($plCarrier) ||
            empty($plDropoff) ||
            empty($plAddress) ||
            $plSession !== $sessionId ||
            $plCarrier != $currentCarrier ||
            $plAddress['countryCode'] !== $shippingAddress['countryCode'] ||
            $plAddress['postalCode'] !== $shippingAddress['postalCode']
        ) {
            $this->invalidateSelectedDropoff();

            return $data;
        }

        $data['isSelected'] = true;
        $data['selectedDropoff'] = $plDropoff;

        return $data;
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
     * Retrieves dropoff service.
     *
     * @return \Packlink\Services\BusinessLogic\DropoffService
     */
    protected function getDropoffService()
    {
        if ($this->dropoffService === null) {
            $this->dropoffService = new DropoffService();
        }

        return $this->dropoffService;
    }

    /**
     * Retrieves checkout service.
     *
     * @return \Packlink\Services\BusinessLogic\CheckoutService
     */
    protected function getCheckoutService()
    {
        if ($this->checkoutService === null) {
            $this->checkoutService = new CheckoutService();
        }

        return $this->checkoutService;
    }

    /**
     * Checks if user is logged in.
     *
     * @return bool
     */
    protected function isLoggedIn()
    {
        $authToken = $this->getConfigService()->getAuthorizationToken();

        return !empty($authToken);
    }

    /**
     * Invalidates selected dropoff.
     */
    protected function invalidateSelectedDropoff()
    {
        Shopware()->Session()->offsetUnset('plSession');
        Shopware()->Session()->offsetUnset('plCarrier');
        Shopware()->Session()->offsetUnset('plDropoff');
        Shopware()->Session()->offsetUnset('plShippingAddress');
    }
}