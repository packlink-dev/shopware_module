<?php

use Packlink\BusinessLogic\Controllers\OrderStatusMappingController;
use Packlink\BusinessLogic\Language\Translator;
use Packlink\Controllers\Common\CanInstantiateServices;
use Packlink\Utilities\Request;
use Shopware\Models\Order\Status;

class Shopware_Controllers_Backend_PacklinkOrderStatusMap extends Enlight_Controller_Action
{
    use CanInstantiateServices;

    /**
     * Retrieves order status mappings.
     */
    public function indexAction()
    {
        $baseController = new OrderStatusMappingController();
        $response = [
            'systemName' => $this->getConfigService()->getIntegrationName(),
            'mappings' => $baseController->getMappings(),
            'packlinkStatuses' => $baseController->getPacklinkStatuses(),
            'orderStatuses' => $this->getAvailableStatuses()
        ];

        $this->View()->assign('response', $response);
    }

    /**
     * Updates order status mappings.
     */
    public function updateAction()
    {
        $data = Request::getPostData();
        $this->getConfigService()->setOrderStatusMappings($data);

        $this->View()->assign('response', []);
    }

    /**
     * Retrieves list of available shop statuses.
     */
    public function listAction()
    {
        $this->View()->assign($this->getAvailableStatuses());
    }

    /**
     * @return array
     */
    protected function getAvailableStatuses()
    {
        $result = ['' => Translator::translate('orderStatusMapping.none')];
        $statuses = $this->getOrderStatuses();
        $snippets = Shopware()->Snippets()->getNamespace('backend/static/order_status');

        foreach ($statuses as $status) {
            $result[$status->getId()] = $snippets->get($status->getName(), $status->getName());
        }

        return $result;
    }

    /**
     * Retrieves list of available order statuses.
     *
     * @return Status[]
     */
    protected function getOrderStatuses()
    {
        $builder = Shopware()->Models()->createQueryBuilder();
        $builder->select(array('status'))
            ->from(Status::class, 'status')
            ->andWhere("status.group = 'state'");

        return $builder->getQuery()->getResult();
    }
}