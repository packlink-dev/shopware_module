<?php

namespace Packlink\Services\BusinessLogic;

use Doctrine\ORM\EntityManagerInterface;
use Packlink\BusinessLogic\CashOnDelivery\Services\OfflinePaymentsServices;
use Packlink\BusinessLogic\Controllers\CashOnDeliveryController;
use Packlink\BusinessLogic\ShippingMethod\Models\ShippingService;
use Packlink\BusinessLogic\ShippingMethod\ShippingMethodService;
use Packlink\Infrastructure\ORM\Exceptions\QueryFilterInvalidParamException;
use Shopware\Models\Payment\Payment;

class OfflinePaymentServices extends OfflinePaymentsServices
{
    /**
     * @var string[]
     */
    protected $knownOffline = [
        'cash',
        'prepayment',
        'invoice',
        'cheque',
        'offline',
    ];

    /**
     * @var CashOnDeliveryController|null
     */
    protected $controller = null;

    /**
     * @var ShippingMethodService
     */
    protected $shippingMethodService;

    /**
     * Constructor.
     *
     * @param ShippingMethodService $shippingMethodService
     */
    public function __construct(ShippingMethodService $shippingMethodService)
    {
        $this->shippingMethodService = $shippingMethodService;
    }

    /**
     *
     * @return array
     */
    public function getOfflinePayments()
    {
        /** @var EntityManagerInterface $em */
        $em = Shopware()->Models();

        $activePayments = $em->getRepository(Payment::class)
            ->findBy(['active' => 1]);

        $offlinePayments = [];

        /** @var Payment $payment */
        foreach ($activePayments as $payment) {
            if ($this->isOffline(strtolower($payment->getName()))) {
                $offlinePayments[] = [
                    'name' => $payment->getName(),
                    'displayName' => $payment->getDescription(),
                ];
            }
        }

        return $offlinePayments;
    }

    /**
     * @throws QueryFilterInvalidParamException
     */
    public function getPaymentsToHide($shippingId, $shippingCountry)
    {
        $paymentsToHide = array();

        $controller = $this->getAccountConfigurationController();
        if ($controller === null) {
            return array();
        }

        $acc = $controller->getCashOnDeliveryConfiguration();

        $services = $this->getShippingServicesForMethod($shippingId);

        $shippingService = null;

        foreach ($services as $service) {
            if ($service->destinationCountry === $shippingCountry) {
                $shippingService = $service;
                break;
            }
        }

        $config = $shippingService && isset($shippingService->cashOnDeliveryConfig)
            ? $shippingService->cashOnDeliveryConfig
            : null;

        if($config &&
            !$config->offered
            && $acc !== null
            && $acc->enabled
            && $acc->active
            && $acc->account)
        {
            $paymentsToHide = array($acc->account->getOfflinePaymentMethod());
        }
        return $paymentsToHide;
    }

    /**
     * @param string $moduleName
     *
     * @return bool
     */
    protected function isOffline($moduleName)
    {
        return in_array($moduleName, $this->knownOffline, true);
    }

    /**
     * Returns shipping services with the given ID.
     *
     * @param int $id Shipping method ID.
     *
     * @return ShippingService[] Services.
     */
    public function getShippingServicesForMethod($id)
    {
        $model = $this->shippingMethodService->getShippingMethod($id);
        $services = array();

        if($model) {
            $services =  $model->getShippingServices();
        }

        return $services;
    }

    /**
     * @return CashOnDeliveryController|null
     */
    protected function getAccountConfigurationController()
    {
        if ($this->controller === null) {
            $this->controller = new CashOnDeliveryController();
        }
        return $this->controller;
    }
}