<?php

namespace Packlink\Services\BusinessLogic;

use Logeecom\Infrastructure\ORM\QueryFilter\Operators;
use Logeecom\Infrastructure\ORM\QueryFilter\QueryFilter;
use Logeecom\Infrastructure\ORM\RepositoryRegistry;
use Packlink\Entities\ShippingMethodMap;

class DropoffService
{
    /** @var \Logeecom\Infrastructure\ORM\Interfaces\RepositoryInterface */
    protected $shippingMethodRepository;
    /**
     * @var \Logeecom\Infrastructure\ORM\Interfaces\RepositoryInterface
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
     * @throws \Logeecom\Infrastructure\ORM\Exceptions\QueryFilterInvalidParamException
     * @throws \Logeecom\Infrastructure\ORM\Exceptions\RepositoryNotRegisteredException
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
     * @return \Logeecom\Infrastructure\ORM\Interfaces\RepositoryInterface
     *
     * @throws \Logeecom\Infrastructure\ORM\Exceptions\RepositoryNotRegisteredException
     */
    protected function getShippingMethodMapRepository()
    {
        if ($this->shippingMethodMapRepository === null) {
            $this->shippingMethodMapRepository = RepositoryRegistry::getRepository(ShippingMethodMap::getClassName());
        }

        return $this->shippingMethodMapRepository;
    }
}