<?php

namespace Packlink\Subscribers;

use Enlight\Event\SubscriberInterface;
use Enlight_Hook_HookArgs;
use Exception;
use Packlink\Infrastructure\Logger\Logger;
use Packlink\Infrastructure\ServiceRegister;
use Packlink\BusinessLogic\Configuration;
use Packlink\Services\BusinessLogic\CheckoutService;
use Packlink\Utilities\Cache;
use Shopware\Models\Dispatch\Dispatch;
use Shopware\Models\Dispatch\ShippingCost;

class ShippingCostCalculator implements SubscriberInterface
{
    protected static $updatedCarriers = [];
    /** @var \Packlink\Services\BusinessLogic\ConfigurationService */
    protected $configService;
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
            'sAdmin::sGetPremiumShippingcosts::before' => 'onBeforeSGetPremiumShippingcosts',
        ];
    }

    /**
     * Handles sAdmin::sGetPremiumShippingcosts::before hook.
     *
     * @param \Enlight_Hook_HookArgs $args
     *
     * @throws \Packlink\Infrastructure\ORM\Exceptions\RepositoryNotRegisteredException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function onBeforeSGetPremiumShippingcosts(Enlight_Hook_HookArgs $args)
    {
        $shippingId = Shopware()->Session()->get('checkoutShippingAddressId');
        $userId = Shopware()->Session()->get('sUserId');
        $sessionId = Shopware()->Session()->get('sessionId');
        $carrierId = Shopware()->Session()->get('sDispatch');

        if (!$this->shouldHandle($userId, $sessionId, $carrierId, $shippingId)) {
            return;
        }

        $shippingId = !empty($shippingId) ? (int)$shippingId : null;

        try {
            $shippingCosts = $this->getCheckoutService()->getShippingCosts((int)$userId, $sessionId, $shippingId);
        } catch (Exception $e) {
            Logger::logError("Failed to calculate shipping cost because: [{$e->getMessage()}].");

            return;
        }

        $carrierId = (int)$carrierId;
        $maps = Cache::getCarrierMaps();

        if (isset($maps[$carrierId], $shippingCosts[$maps[$carrierId]])) {
            $cost = (float)$shippingCosts[$maps[$carrierId]];
            $this->updateCost($carrierId, $cost);
            static::$updatedCarriers[] = $carrierId;
        } else if (($backupId = $this->getConfigService()->getBackupCarrierId()) === $carrierId) {
            $cost = min(array_values($shippingCosts));
            $this->updateCost($backupId, $cost);
            static::$updatedCarriers[] = $backupId;
        }
    }

    /**
     * Checks whether hook should be handled.
     *
     * @param string | null $userId
     * @param string | null $sessionId
     * @param string | null $carrierId
     * @param string | null $shippingId
     *
     * @return bool
     * @throws \Packlink\Infrastructure\ORM\Exceptions\RepositoryNotRegisteredException
     */
    protected function shouldHandle($userId, $sessionId, $carrierId, $shippingId)
    {
        return $this->isLoggedIn()
            && !empty($sessionId)
            && (!empty($shippingId) || !empty($userId))
            && !empty($carrierId)
            && $this->isPacklinkCarrier((int)$carrierId)
            && !$this->isCarrierUpdated((int)$carrierId);
    }

    /**
     * Checks whether carrier is packlink carrier.
     *
     * @param int $carrierId
     *
     * @return bool
     * @throws \Packlink\Infrastructure\ORM\Exceptions\RepositoryNotRegisteredException
     */
    protected function isPacklinkCarrier($carrierId)
    {
        $packlinkCarriers = Cache::getPacklinkCarriers();
        $backupId = $this->getConfigService()->getBackupCarrierId();

        return in_array($carrierId, $packlinkCarriers, true) || ($backupId !== null && $backupId === $carrierId);
    }

    /**
     * Checks whether carrier is already updated.
     *
     * @param int $carrierId
     *
     * @return bool
     */
    protected function isCarrierUpdated($carrierId)
    {
        return in_array($carrierId, static::$updatedCarriers, true);
    }

    /**
     * Updates shipping cost.
     *
     * @param int $carrierId
     * @param float $cost
     *
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    protected function updateCost($carrierId, $cost)
    {
        /** @var \Shopware\Models\Dispatch\Repository $carrierRepository */
        $carrierRepository = Shopware()->Models()->getRepository(Dispatch::class);
        /** @var Dispatch $carrier */
        if (($carrier = $carrierRepository->find($carrierId)) !== null) {
            $carrierRepository->getPurgeShippingCostsMatrixQuery($carrierId)->execute();
            $carrier->setCalculation(1);
            $shippingCost = new ShippingCost();
            $shippingCost->setFactor(0);
            $shippingCost->setValue($cost);
            $shippingCost->setFrom(0);
            $shippingCost->setDispatch($carrier);

            Shopware()->Models()->persist($carrier);
            Shopware()->Models()->persist($shippingCost);
            Shopware()->Models()->flush();
        }
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
}