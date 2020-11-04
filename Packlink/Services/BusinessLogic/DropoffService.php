<?php

namespace Packlink\Services\BusinessLogic;

use Packlink\Infrastructure\ORM\QueryFilter\Operators;
use Packlink\Infrastructure\ORM\QueryFilter\QueryFilter;
use Packlink\Infrastructure\ORM\RepositoryRegistry;
use Packlink\Entities\ShippingMethodMap;

class DropoffService
{
    /** @var \Packlink\Infrastructure\ORM\Interfaces\RepositoryInterface */
    protected $shippingMethodRepository;
    /**
     * @var \Packlink\Infrastructure\ORM\Interfaces\RepositoryInterface
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
     * @throws \Packlink\Infrastructure\ORM\Exceptions\QueryFilterInvalidParamException
     * @throws \Packlink\Infrastructure\ORM\Exceptions\RepositoryNotRegisteredException
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
     * @return \Packlink\Infrastructure\ORM\Interfaces\RepositoryInterface
     *
     * @throws \Packlink\Infrastructure\ORM\Exceptions\RepositoryNotRegisteredException
     */
    protected function getShippingMethodMapRepository()
    {
        if ($this->shippingMethodMapRepository === null) {
            $this->shippingMethodMapRepository = RepositoryRegistry::getRepository(ShippingMethodMap::getClassName());
        }

        return $this->shippingMethodMapRepository;
    }
}