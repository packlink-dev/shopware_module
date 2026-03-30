<?php

use Packlink\BusinessLogic\CashOnDelivery\Services\OfflinePaymentsServices;
use Packlink\BusinessLogic\Controllers\CashOnDeliveryController;
use Packlink\Controllers\Common\CanInstantiateServices;
use Packlink\Infrastructure\ORM\Exceptions\QueryFilterInvalidParamException;
use Packlink\Infrastructure\ServiceRegister;
use Packlink\Utilities\Request;

class  Shopware_Controllers_Backend_PacklinkCashOnDelivery extends Enlight_Controller_Action
{
    use CanInstantiateServices;

    /**
     * @var CashOnDeliveryController|null $controller
     */
    protected $controller;

    /**
     * Retrieves offline payment methods and COD configuration.
     *
     * @throws \Packlink\Infrastructure\ORM\Exceptions\QueryFilterInvalidParamException
     */
    public function indexAction()
    {
        $service = ServiceRegister::getService(OfflinePaymentsServices::CLASS_NAME);

        $controller    = $this->getAccountConfigurationController();
        $configuration = ($controller !== null) ? $controller->getCashOnDeliveryConfiguration() : null;

        $configArray = array();

        if ($configuration !== null) {
            $configArray = $configuration->toArray();
        }

        $this->View()->assign([
            'response' => [
                'paymentMethods' => $service->getOfflinePayments(),
                'configuration' => $configArray,
            ]
        ]);
    }

    /**
     * @throws \Packlink\BusinessLogic\DTO\Exceptions\FrontDtoValidationException
     */
    public function updateAction()
    {
        try {
            $data = Request::getPostData();
            $controller = $this->getAccountConfigurationController();

            if (!$controller) {
                throw new \RuntimeException('CashOnDeliveryController not available.');
            }

            $controller->saveConfig($data);

            $response = ['success' => true, 'message' => 'Configuration saved successfully.'];
        } catch (\Throwable $e) {
            $response = ['success' => false, 'message' => $e->getMessage()];
        }

        $this->View()->assign(['response' => $response]);
    }


    /**
     * Retrieves Packlink account configuration and checks if an account exists.
     *
     * @return CashOnDeliveryController
     *
     * @throws QueryFilterInvalidParamException
     */
    private function getAccountConfigurationController()
    {
        if($this->controller === null) {
            $this->controller = new CashOnDeliveryController();
        }

        return $this->controller;
    }
}