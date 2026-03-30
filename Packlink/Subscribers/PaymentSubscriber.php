<?php

namespace Packlink\Subscribers;

use Enlight\Event\SubscriberInterface;
use Packlink\BusinessLogic\CashOnDelivery\Services\OfflinePaymentsServices;
use Packlink\Infrastructure\ServiceRegister;
use Packlink\Utilities\Cache;

class PaymentSubscriber implements SubscriberInterface
{
    /** @var OfflinePaymentsServices $offlineService */
    protected $offlineService;

    /**
     * @return string[]
     */
    public static function getSubscribedEvents()
    {
        return [
            'sAdmin::sGetPaymentMeans::after' => 'filterPaymentMethodsByShipping'
        ];
    }

    /**
     * @param \Enlight_Hook_HookArgs $args
     *
     * @return void
     *
     * @throws \Packlink\Infrastructure\ORM\Exceptions\QueryFilterInvalidParamException
     * @throws \Packlink\Infrastructure\ORM\Exceptions\RepositoryNotRegisteredException
     */
    public function filterPaymentMethodsByShipping(\Enlight_Hook_HookArgs $args)
    {
        $result = $args->getReturn();

        $carrierId = Shopware()->Session()->get('sDispatch');
        if (!$carrierId) {
            $args->setReturn($result);
            return;
        }

        $userData = array();
        $admin = Shopware()->Modules()->Admin();
        if ($admin && method_exists($admin, 'sGetUserData')) {
            $userData = $admin->sGetUserData();
        }

        $shippingCountry = null;
        if (isset($userData['additional']['countryShipping']['countryiso'])) {
            $shippingCountry = $userData['additional']['countryShipping']['countryiso'];
        }

        $maps = Cache::getCarrierMaps();
        $shippingId = isset($maps[$carrierId]) ? $maps[$carrierId] : null;
        if (!$shippingId) {
            $args->setReturn($result);
            return;
        }

        $offlineService = $this->getOfflinePaymentService();

        $paymentsToHide = ($offlineService !== null) ?
            $offlineService->getPaymentsToHide($shippingId, $shippingCountry) : array();

        if (!is_array($paymentsToHide)) {
            $paymentsToHide = array();
        }

        foreach ($result as $key => $payment) {
            if (in_array($payment['name'], $paymentsToHide, true)) {
                unset($result[$key]);
            }
        }

        $args->setReturn(array_values($result));
    }


    /**
     * @return OfflinePaymentsServices|null
     */
    protected function getOfflinePaymentService()
    {
        if ($this->offlineService === null) {
            $this->offlineService = ServiceRegister::getService(OfflinePaymentsServices::CLASS_NAME);
        }
        return $this->offlineService;
    }
}