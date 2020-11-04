<?php

namespace Packlink\Subscribers;

use Enlight\Event\SubscriberInterface;
use Enlight_Event_EventArgs;
use Packlink\Infrastructure\ServiceRegister;
use Packlink\BusinessLogic\Configuration;

class DropoffMailHandler implements SubscriberInterface
{
    /**
     * @var \Packlink\Services\BusinessLogic\ConfigurationService
     */
    protected $configService;

    /**
     * @inheritDoc
     */
    public static function getSubscribedEvents()
    {
        return [
            'Shopware_Modules_Order_SendMail_FilterVariables' => 'addDropoffToEmail',
        ];
    }

    /**
     * Adds dropoff address to email.
     *
     * @param \Enlight_Event_EventArgs $args
     *
     * @return array | null
     */
    public function addDropoffToEmail(Enlight_Event_EventArgs $args)
    {
        $result = null;

        if (!$this->isLoggedIn()) {
            return $result;
        }

        $plDropoff = Shopware()->Session()->get('plDropoff');
        if (!empty($plDropoff)) {
            $result = $args->getReturn();
            if (isset($result['shippingaddress'])) {
                $result['shippingaddress']['street'] = $plDropoff['address'];
                $result['shippingaddress']['zipcode'] = $plDropoff['zip'];
                $result['shippingaddress']['city'] = $plDropoff['city'];
            }
        }

        $this->invalidateSelectedDropoff();

        return $result;
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
}