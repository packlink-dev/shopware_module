<?php

namespace Packlink\Subscribers;

use Doctrine\ORM\Exception\NotSupported;
use Enlight\Event\SubscriberInterface;
use Packlink\BusinessLogic\CashOnDelivery\Services\OfflinePaymentsServices;
use Packlink\Infrastructure\ORM\Exceptions\QueryFilterInvalidParamException;
use Packlink\Infrastructure\ORM\Exceptions\RepositoryNotRegisteredException;
use Packlink\Infrastructure\ServiceRegister;
use Packlink\Utilities\Cache;

class SurchargeHandler implements SubscriberInterface
{
    /** @var OfflinePaymentsServices $offlineService */
    protected $offlineService;

    /**
     * @return string[]
     */
    public static function getSubscribedEvents()
    {
        return [
            'Shopware_Modules_Basket_GetBasket_FilterResult' => 'addPaymentSurcharge'
        ];
    }

    /**
     * Adds a payment surcharge to the basket data based on payment and shipping details.
     *
     * @param \Enlight_Event_EventArgs $args Contains the event arguments, including the basket data.
     *
     * @return array Updated basket data with the payment surcharge applied.
     *
     * @throws NotSupported
     * @throws QueryFilterInvalidParamException
     * @throws RepositoryNotRegisteredException
     */
    public function addPaymentSurcharge(\Enlight_Event_EventArgs $args)
    {
        /** @var array $basketData */
        $basketData = $args->getReturn();

        $paymentId  = $this->getPaymentId();


        $payment = Shopware()->Models()->getRepository(\Shopware\Models\Payment\Payment::class)->find($paymentId);

        if ($payment) {
            $paymentName = $payment->getName();
        }

        $shippingId = $this->getShippingId();

        $shippingCountry = $this->getShippingCountry();

        $totalAmount = isset($basketData['AmountNumeric']) ? (float)$basketData['AmountNumeric'] : 0.0;

        $surchargeAmount = $this->getOfflinePaymentService()->calculateFee($shippingId, $paymentName, $shippingCountry, $totalAmount);

        $basketData['AmountNumeric'] += (float)$surchargeAmount;
        $basketData['Amount'] = number_format($basketData['AmountNumeric'], 2, '.', '');

        $basketData['AmountNetNumeric'] += (float)$surchargeAmount;
        $basketData['AmountNet'] = number_format($basketData['AmountNetNumeric'], 2, '.', '');

        if( $surchargeAmount > 0) {
            $basketData['sCODSurcharge'] = $surchargeAmount;
        }

        return $basketData;
    }

    private function getPaymentId()
    {
        $request = Shopware()->Front()->Request();

        if ($request && $request->isPost() && $request->getPost('payment')) {
            $paymentId = (int) $request->getPost('payment');
            Shopware()->Session()->offsetSet('plLastPaymentId', $paymentId);
            return $paymentId;
        }

        if ($id = Shopware()->Session()->offsetGet('plLastPaymentId')) {
            return (int) $id;
        }

        if (!empty(Shopware()->Session()->sPaymentID)) {
            return (int) Shopware()->Session()->sPaymentID;
        }

        $userData = Shopware()->Modules()->Admin()->sGetUserData();
        if (isset($userData['additional']['payment']['id'])) {
            return (int) $userData['additional']['payment']['id'];
        }

        return 0;
    }

    /**
     * Returns the shipping method ID (shippingId) based on the current session's carrier.
     *
     * @return int
     *
     * @throws RepositoryNotRegisteredException
     */
    private function getShippingId()
    {
        $carrierId = (int) Shopware()->Session()->sDispatch;
        if ($carrierId === 0) {
            return 0;
        }

        $maps = Cache::getCarrierMaps();
        return isset($maps[$carrierId]) ? (int)$maps[$carrierId] : 0;
    }

    private function getShippingCountry()
    {
        $userData = Shopware()->Modules()->Admin()->sGetUserData();

        if (isset($userData['additional']['countryShipping']['countryiso'])) {
            return $userData['additional']['countryShipping']['countryiso'];
        }

        return '';
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