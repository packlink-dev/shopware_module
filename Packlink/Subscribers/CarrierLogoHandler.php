<?php

namespace Packlink\Subscribers;

use Enlight\Event\SubscriberInterface;
use Enlight_Hook_HookArgs;
use Logeecom\Infrastructure\ServiceRegister;
use Packlink\BusinessLogic\Configuration;
use Packlink\BusinessLogic\ShippingMethod\Interfaces\ShopShippingMethodService;
use Packlink\Utilities\Cache;

class CarrierLogoHandler implements SubscriberInterface
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
            'sAdmin::sGetPremiumDispatches::after' => 'onAfterSGetPremiumDispatches',
        ];
    }

    /**
     * @param \Enlight_Hook_HookArgs $args
     *
     * @throws \Logeecom\Infrastructure\ORM\Exceptions\QueryFilterInvalidParamException
     * @throws \Logeecom\Infrastructure\ORM\Exceptions\RepositoryNotRegisteredException
     * @throws \Exception
     */
    public function onAfterSGetPremiumDispatches(Enlight_Hook_HookArgs $args)
    {
        if (!$this->shouldHandle()) {
            return;
        }

        $carriers = $args->getReturn();
        $map = Cache::getCarrierMaps();
        /** @var \Packlink\Services\BusinessLogic\ShopShippingMethodService $shopShippingMethodService */
        $shopShippingMethodService = ServiceRegister::getService(ShopShippingMethodService::CLASS_NAME);
        foreach ($carriers as $index => $carrier) {
            $id = (int) $carrier['id'];
            $isBackup = $this->isBackupCarrier((int) $carrier['id']);
            if ($isBackup) {
                $carriers[$index]['isPlLogoEnabled'] = true;
                $carriers[$index]['plLogo'] = $shopShippingMethodService->getCarrierLogoFilePath('backup');
            } else if (isset($map[$id])) {
                $service = Cache::getService($map[$id]);

                if ($service && $service->isDisplayLogo()) {
                    $carriers[$index]['isPlLogoEnabled'] = true;
                    $carriers[$index]['plLogo'] = $service->getLogoUrl();
                }
            }
        }

        $args->setReturn($carriers);
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
     * Checks whether event should be handled.
     *
     * @return bool
     */
    protected function shouldHandle()
    {
        return $this->isLoggedIn();
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
     * Checks whether carrier is backup or not.
     *
     * @param int $carrierId
     *
     * @return bool
     */
    protected function isBackupCarrier($carrierId)
    {
        $backupId = $this->getConfigService()->getBackupCarrierId();

        return $backupId !== null && $backupId === $carrierId;
    }
}