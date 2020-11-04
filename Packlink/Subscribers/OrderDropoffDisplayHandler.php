<?php

namespace Packlink\Subscribers;

use Enlight\Event\SubscriberInterface;
use Enlight_Event_EventArgs;
use Packlink\Core\Infrastructure\ORM\QueryFilter\Operators;
use Packlink\Core\Infrastructure\ORM\QueryFilter\QueryFilter;
use Packlink\Core\Infrastructure\ORM\RepositoryRegistry;
use Packlink\Entities\OrderDropoffMap;

class OrderDropoffDisplayHandler implements SubscriberInterface
{
    /**
     * @var \Packlink\Core\Infrastructure\ORM\Interfaces\RepositoryInterface
     */
    protected $orderDropoffMapRepository;

    /**
     * @inheritDoc
     */
    public static function getSubscribedEvents()
    {
        return ['Shopware_Modules_Admin_GetOpenOrderData_FilterResult' => 'displayDropoffInformation'];
    }

    /**
     * Appends dropoff information on order.
     *
     * @param \Enlight_Event_EventArgs $args
     *
     * @return array
     *
     * @throws \Packlink\Core\Infrastructure\ORM\Exceptions\QueryFilterInvalidParamException
     * @throws \Packlink\Core\Infrastructure\ORM\Exceptions\RepositoryNotRegisteredException
     */
    public function displayDropoffInformation(Enlight_Event_EventArgs $args)
    {
        $return = $args->getReturn();

        foreach ($return as $index => $order) {
            $query = new QueryFilter();
            $query->where('orderId', Operators::EQUALS, (int)$order['id']);
            /** @var OrderDropoffMap $map */
            $map = $this->getOrderDropoffMapRepository()->selectOne($query);
            if ($map) {
                $return[$index]['plHasDropoff'] = true;
                $return[$index]['plDropoffInfo'] = implode(
                    ', ',
                    [$map->dropoff['name'], $map->dropoff['address'], $map->dropoff['city']]
                );
            }
        }

        return $return;
    }

    /**
     * Retrieves order dropoff map repository.
     *
     * @return \Packlink\Core\Infrastructure\ORM\Interfaces\RepositoryInterface
     *
     * @throws \Packlink\Core\Infrastructure\ORM\Exceptions\RepositoryNotRegisteredException
     */
    protected function getOrderDropoffMapRepository()
    {
        if ($this->orderDropoffMapRepository === null) {
            $this->orderDropoffMapRepository = RepositoryRegistry::getRepository(OrderDropoffMap::getClassName());
        }

        return $this->orderDropoffMapRepository;
    }
}