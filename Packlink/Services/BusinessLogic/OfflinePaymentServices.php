<?php

namespace Packlink\Services\BusinessLogic;

use Doctrine\ORM\EntityManagerInterface;
use Packlink\BusinessLogic\CashOnDelivery\Services\OfflinePaymentsServices;
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
     * @param string $moduleName
     * @return bool
     */
    protected function isOffline($moduleName)
    {
        return in_array($moduleName, $this->knownOffline, true);
    }
}