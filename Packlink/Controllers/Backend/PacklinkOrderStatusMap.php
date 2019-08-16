<?php

use Packlink\Controllers\Common\CanInstantiateServices;
use Packlink\Utilities\Request;
use Packlink\Utilities\Response;
use Shopware\Components\CSRFWhitelistAware;
use Shopware\Models\Order\Status;

class Shopware_Controllers_Backend_PacklinkOrderStatusMap extends Enlight_Controller_Action implements CSRFWhitelistAware
{
    use CanInstantiateServices;

    /**
     * Returns a list with actions which should not be validated for CSRF protection
     *
     * @return string[]
     */
    public function getWhitelistedCSRFActions()
    {
        return ['index', 'update', 'list'];
    }

    /**
     * Retrieves order status mappings.
     */
    public function indexAction()
    {
        $mappings = $this->getConfigService()->getOrderStatusMappings();
        $mappings = $mappings ?: [];

        Response::json($mappings);
    }

    /**
     * Updates order status mappings.
     */
    public function updateAction()
    {
        $data = Request::getPostData();
        $this->getConfigService()->setOrderStatusMappings($data);

        Response::json();
    }

    /**
     * Retrieves list of available shop statuses.
     */
    public function listAction()
    {
        $result = [];

        $statuses = $this->getOrderStatuses();
        $snippets = Shopware()->Snippets()->getNamespace('backend/static/order_status');
        foreach ($statuses as $status) {
            $result[] = [
                'code' => $status->getId(),
                'label' => $snippets->get($status->getName(), $status->getName()),
            ];
        }

        Response::json($result);
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