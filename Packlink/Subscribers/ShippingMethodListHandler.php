<?php

namespace Packlink\Subscribers;

use Enlight\Event\SubscriberInterface;
use Enlight_Hook_HookArgs;
use Exception;
use Logeecom\Infrastructure\Logger\Logger;
use Logeecom\Infrastructure\ServiceRegister;
use Packlink\Bootstrap\Bootstrap;
use Packlink\BusinessLogic\Configuration;
use Packlink\Services\BusinessLogic\CheckoutService;
use Packlink\Utilities\Cache;

class ShippingMethodListHandler implements SubscriberInterface
{
    /**
     * @var \Packlink\Services\BusinessLogic\CheckoutService
     */
    protected $checkoutService;
    /**
     * @var \Packlink\Services\BusinessLogic\ConfigurationService
     */
    private $configService;

    /**
     * @inheritDoc
     */
    public static function getSubscribedEvents()
    {
        return [
            'sAdmin::sGetPremiumDispatches::after' => 'onAfterSGetPremiumDispatches',
        ];
    }

    /**
     * Handles sAdmin::sGetPremiumDispatches::after hook.
     *
     * @param \Enlight_Hook_HookArgs $args
     *
     * @throws \Logeecom\Infrastructure\ORM\Exceptions\RepositoryNotRegisteredException
     */
    public function onAfterSGetPremiumDispatches(Enlight_Hook_HookArgs $args)
    {
        Bootstrap::init();

        $shippingId = Shopware()->Session()->get('checkoutShippingAddressId');
        $userId = Shopware()->Session()->get('sUserId');
        $sessionId = Shopware()->Session()->get('sessionId');

        if (!$this->shouldHandle($userId, $sessionId)) {
            return;
        }

        $shippingId = !empty($shippingId) ? (int)$shippingId : null;
        try {
            $costs = $this->getCheckoutService()->getShippingCosts($userId, $sessionId, $shippingId);
        } catch (Exception $e) {
            Logger::logError("Failed to retrieve available carriers [$userId, $sessionId].");
        }

        $carriers = $args->getReturn();
        $packlinkCarriers = Cache::getPacklinkCarriers();
        $maps = Cache::getCarrierMaps();
        foreach ($carriers as $index => $carrier) {
            $id = (int)$carrier['id'];
            /** @noinspection NotOptimalIfConditionsInspection */
            if (in_array($id, $packlinkCarriers, true) && !isset($costs[$maps[$id]])) {
                unset($carriers[$id]);
            }
        }

        if (empty($costs) || count($carriers) !== 1) {
            $carriers = $this->removeBackupCarrier($carriers);
        }

        $args->setReturn($carriers);
    }

    /**
     * Checks whether hook should be handled.
     *
     * @param string | null $userId
     * @param string | null $sessionId
     *
     * @return bool
     */
    protected function shouldHandle($userId, $sessionId)
    {
        return !empty($userId) && !empty($sessionId);
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
     * Removes backup carrier.
     *
     * @param array $carriers
     *
     * @return array
     */
    protected function removeBackupCarrier($carriers)
    {
        if (($backupId = $this->getConfigService()->getBackupCarrierId()) !== null) {
            foreach ($carriers as $index => $carrier) {
                if ((int)$carrier['id'] === $backupId) {
                    unset($carriers[$index]);
                }
            }
        }

        return $carriers;
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