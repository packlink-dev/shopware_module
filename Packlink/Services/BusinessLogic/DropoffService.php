<?php

namespace Packlink\Services\BusinessLogic;

use Packlink\Core\Infrastructure\ORM\QueryFilter\Operators;
use Packlink\Core\Infrastructure\ORM\QueryFilter\QueryFilter;
use Packlink\Core\Infrastructure\ORM\RepositoryRegistry;
use Packlink\Entities\ShippingMethodMap;

class DropoffService
{
    /** @var \Packlink\Core\Infrastructure\ORM\Interfaces\RepositoryInterface */
    protected $shippingMethodRepository;
    /**
     * @var \Packlink\Core\Infrastructure\ORM\Interfaces\RepositoryInterface
     */
    protected $shippingMethodMapRepository;

    /**
     * Retrieves dropoff carrier ids.
     * [
     *      shippingMethodId => shopwareDispatchId,
     *      ...,
     * ]
     *
     * @return array
     *
     * @throws \Packlink\Core\Infrastructure\ORM\Exceptions\QueryFilterInvalidParamException
     * @throws \Packlink\Core\Infrastructure\ORM\Exceptions\RepositoryNotRegisteredException
     */
    public function getDropoffCarriers()
    {
        $result = [];
        $query = new QueryFilter();
        $query->where('isDropoff', Operators::EQUALS, true);

        $maps = $this->getShippingMethodMapRepository()->select($query);
        /** @var ShippingMethodMap $map */
        foreach ($maps as $map) {
            $result[$map->shopwareCarrierId] = $map->shippingMethodId;
        }

        return $result;
    }

    /**
     * Retrieves shipping method map repository.
     *
     * @return \Packlink\Core\Infrastructure\ORM\Interfaces\RepositoryInterface
     *
     * @throws \Packlink\Core\Infrastructure\ORM\Exceptions\RepositoryNotRegisteredException
     */
    protected function getShippingMethodMapRepository()
    {
        if ($this->shippingMethodMapRepository === null) {
            $this->shippingMethodMapRepository = RepositoryRegistry::getRepository(ShippingMethodMap::getClassName());
        }

        return $this->shippingMethodMapRepository;
    }
}