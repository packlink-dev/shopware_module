<?php

namespace Packlink\Subscribers;

use Enlight\Event\SubscriberInterface;
use Enlight_Controller_ActionEventArgs;
use Enlight_Event_EventArgs;
use Exception;
use Packlink\Infrastructure\ORM\RepositoryRegistry;
use Packlink\Infrastructure\ServiceRegister;
use Packlink\BusinessLogic\Configuration;
use Packlink\Entities\OrderDropoffMap;
use Packlink\Services\BusinessLogic\CheckoutService;
use Packlink\Services\BusinessLogic\DropoffService;

class DropoffHandler implements SubscriberInterface
{
    const DEFUALT_LANGUAGE = 'en';

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
    /** @var \Shopware\Components\ProductStream\RepositoryInterface */
    protected $orderDropoffMapRepository;

    /**
     * @inheritDoc
     */
    public static function getSubscribedEvents()
    {
        return [
            'Enlight_Controller_Action_PostDispatch_Frontend_Checkout' => 'onCheckoutPostDispatch',
            'Enlight_Controller_Action_PreDispatch_Frontend_Checkout' => 'onCheckoutPreDispatch',
            'Shopware_Modules_Order_SaveShipping_FilterArray' => 'saveDropoff',
        ];
    }

    /**
     * Handles Enlight_Controller_Action_PreDispatch_Frontend_Checkout event.
     *
     * @param \Enlight_Controller_ActionEventArgs $args
     */
    public function onCheckoutPreDispatch(Enlight_Controller_ActionEventArgs $args)
    {
        if (!$this->shouldHandlePreDispatch($args)) {
            return;
        }

        $isDropoff = Shopware()->Session()->get('plIsDropoff');
        $isSelected = Shopware()->Session()->get('plIsSelected');

        if ($isDropoff && !$isSelected) {
            $args->getSubject()->forward('confirm');
        }
    }

    /**
     * Handles post dispatch event for Frontend Checkout controller.
     *
     * @param \Enlight_Controller_ActionEventArgs $args
     *
     * @throws \Packlink\Infrastructure\ORM\Exceptions\QueryFilterInvalidParamException
     * @throws \Packlink\Infrastructure\ORM\Exceptions\RepositoryNotRegisteredException
     */
    public function onCheckoutPostDispatch(Enlight_Controller_ActionEventArgs $args)
    {
        if (!$this->shouldHandlePostDispatch($args)) {
            return;
        }

        $data = [
            'plIsLoggedIn' => false,
        ];

        $viewData = $this->getViewData();

        if ($this->isLoggedIn()) {
            $data['plIsLoggedIn'] = true;
            $data['plConfig'] = json_encode($viewData);
            $data['plIsSelected'] = $viewData['dropOff']['isSelected'];
            $data['plIsDropoff'] = array_key_exists($viewData['dropOff']['carrier'], $viewData['dropOffs']);
            Shopware()->Session()->offsetSet('plIsDropoff', $data['plIsDropoff']);
            Shopware()->Session()->offsetSet('plIsSelected', $data['plIsSelected']);
            $data['plLang'] = $this->getCurrentLanguage();
        }

        $args->getSubject()->View()->assign($data);
    }

    /**
     * Saves selected drop off.
     *
     * @param \Enlight_Event_EventArgs $args
     *
     * @return array|null
     *
     * @throws \Packlink\Infrastructure\ORM\Exceptions\RepositoryNotRegisteredException
     */
    public function saveDropoff(Enlight_Event_EventArgs $args)
    {
        $result = null;

        if (!$this->shouldHandleSaveDropoff()) {
            return $result;
        }

        $plDropoff = Shopware()->Session()->get('plDropoff');
        if (!empty($plDropoff)) {
            $this->doLinkOrderAndDropoff((int) $args->get('id'), $plDropoff);
            $result =  $this->doSaveDropoff($args, $plDropoff);
        }

        return $result;
    }

    /**
     * Retrieves view data.
     *
     * @return array
     *
     * @throws \Packlink\Infrastructure\ORM\Exceptions\QueryFilterInvalidParamException
     * @throws \Packlink\Infrastructure\ORM\Exceptions\RepositoryNotRegisteredException
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
        $data['carrier'] = $currentCarrier = Shopware()->Session()->get('sDispatch');
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

    /**
     * Checks whether event should be handled.
     *
     * @param \Enlight_Controller_ActionEventArgs $args
     *
     * @return bool
     */
    protected function shouldHandlePostDispatch(Enlight_Controller_ActionEventArgs $args)
    {
        return $this->isLoggedIn() && $this->isPermittedAction($args);
    }

    /**
     * Checks whether proper action is performed.
     *
     * @param \Enlight_Controller_ActionEventArgs $args
     *
     * @return bool
     */
    protected function isPermittedAction(Enlight_Controller_ActionEventArgs $args)
    {
        $request = $args->getRequest();
        $targetAction = $args->getSubject()->View()->getAssign('sTargetAction');

        return $request->getActionName() === 'shippingPayment' || $targetAction === 'confirm';
    }

    /**
     * Checks wheter predispatch hook can be handled.
     *
     * @param \Enlight_Controller_ActionEventArgs $args
     *
     * @return bool
     */
    protected function shouldHandlePreDispatch(Enlight_Controller_ActionEventArgs $args)
    {
        return $this->isLoggedIn() && $args->getRequest()->getActionName() === 'finish';
    }

    /**
     * Checks whether dropoff save should be handled.
     *
     * @return bool
     */
    protected function shouldHandleSaveDropoff()
    {
        return $this->isLoggedIn();
    }

    /**
     * Saves selected dropoff.
     *
     * @param Enlight_Event_EventArgs $args
     * @param $plDropoff
     *
     * @return array
     */
    protected function doSaveDropoff(Enlight_Event_EventArgs $args, array  $plDropoff)
    {
        $result = $args->getReturn();

        $result['street'] = $plDropoff['address'];
        $result['zipcode'] = $plDropoff['zip'];
        $result['city'] = $plDropoff['city'];

        return $result;
    }

    /**
     * Links order and selected dropoff.
     *
     * @param int $id
     * @param array $plDropoff
     *
     * @throws \Packlink\Infrastructure\ORM\Exceptions\RepositoryNotRegisteredException
     */
    protected function doLinkOrderAndDropoff($id, array $plDropoff)
    {
        $map = new OrderDropoffMap();
        $map->orderId = $id;
        $map->dropoff = $plDropoff;
        $this->getOrderDropoffMapRepository()->save($map);
    }

    /**
     * Retrieves order dropoff map repository.
     *
     * @return \Packlink\Infrastructure\ORM\Interfaces\RepositoryInterface
     * @throws \Packlink\Infrastructure\ORM\Exceptions\RepositoryNotRegisteredException
     */
    protected function getOrderDropoffMapRepository()
    {
        if ($this->orderDropoffMapRepository === null) {
            $this->orderDropoffMapRepository = RepositoryRegistry::getRepository(OrderDropoffMap::getClassName());
        }

        return $this->orderDropoffMapRepository;
    }

    /**
     * Retrieves current language.
     *
     * @return string
     */
    protected function getCurrentLanguage()
    {
        $shop = Shopware()->Shop();

        if (!$shop) {
            return self::DEFUALT_LANGUAGE;
        }

        $locale = $shop->getLocale();

        if (!$locale) {
            return self::DEFUALT_LANGUAGE;
        }

        $lang = explode('_', $locale->getLocale());

        return $lang[0];
    }
}