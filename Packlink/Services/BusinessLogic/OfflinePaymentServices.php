<?php

namespace Packlink\Services\BusinessLogic;

use Doctrine\ORM\EntityManagerInterface;
use Packlink\BusinessLogic\CashOnDelivery\Interfaces\CashOnDeliveryServiceInterface;
use Packlink\BusinessLogic\CashOnDelivery\Services\CashOnDeliveryService;
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
     * @var CashOnDeliveryServiceInterface $cashOnDeliveryService
     */
    protected $cashOnDeliveryService;

    /**
     * Constructor.
     *
     * @param ShippingMethodService $shippingMethodService
     * @param CashOnDeliveryServiceInterface $cashOnDeliveryService
     */
    public function __construct(ShippingMethodService $shippingMethodService, CashOnDeliveryServiceInterface $cashOnDeliveryService)
    {
        $this->shippingMethodService = $shippingMethodService;
        $this->cashOnDeliveryService = $cashOnDeliveryService;
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
     * Calculate COD surcharge fee based on Packlink rules.
     *
     * @param $shippingId
     * @param $paymentMethodId
     * @param $shippingCountry
     * @param $orderTotal
     *
     * @return float COD surcharge
     *
     * @throws QueryFilterInvalidParamException
     */
    public function calculateFee($shippingId, $paymentMethodId, $shippingCountry, $orderTotal)
    {
        $controller = $this->getAccountConfigurationController();
        if ($controller === null) {
            return 0;
        }

        $cod = $controller->getCashOnDeliveryConfiguration();

        if (!$cod || !$cod->account || $cod->account->getOfflinePaymentMethod() !== $paymentMethodId) {
            return 0;
        }

        if($cod->account->getCashOnDeliveryFee() !== null)
        {
            return $cod->account->getCashOnDeliveryFee();
        }

        $services = $this->getShippingServicesForMethod($shippingId);

        $shippingService = null;

        foreach ($services as $service) {
            if ($service->destinationCountry === $shippingCountry) {
                $shippingService = $service;
                break;
            }
        }

        if ($shippingService && $shippingService->cashOnDeliveryConfig) {
            return $this->cashOnDeliveryService->calculateFee(
                $orderTotal,
                $shippingService->cashOnDeliveryConfig->applyPercentageCashOnDelivery,
                $shippingService->cashOnDeliveryConfig->maxCashOnDelivery
            );
        }

        return 0;
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