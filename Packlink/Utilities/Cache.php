<?php

namespace Packlink\Utilities;

use Packlink\Infrastructure\ORM\QueryFilter\Operators;
use Packlink\Infrastructure\ORM\QueryFilter\QueryFilter;
use Packlink\Infrastructure\ORM\RepositoryRegistry;
use Packlink\BusinessLogic\Http\DTO\ParcelInfo;
use Packlink\BusinessLogic\Warehouse\Warehouse;
use Packlink\BusinessLogic\ShippingMethod\Models\ShippingMethod;
use Packlink\Entities\ShippingMethodMap;

class Cache
{
    protected static $packlinkCarriers = [];
    protected static $shippingCosts = [];
    protected static $carrierMaps = [];
    /** @var Warehouse */
    protected static $defaultWarehouse;
    /** @var \Packlink\Infrastructure\ORM\Interfaces\RepositoryInterface */
    protected static $shippingMapRepository;
    /**
     * @var \Packlink\BusinessLogic\Http\DTO\ParcelInfo
     */
    private static $defaultParcel;
    /**
     * @var array
     */
    private static $shippingAddress = [];
    /**
     * @var array
     */
    private static $parcelItems;
    /**
     * @var array
     */
    private static $services = [];
    /** @var \Packlink\Infrastructure\ORM\Interfaces\RepositoryInterface */
    protected static $shippingServicesRepository;

    /**
     * Returns shipping method maps.
     *
     * @return array
     *
     * @throws \Packlink\Infrastructure\ORM\Exceptions\RepositoryNotRegisteredException
     */
    public static function getCarrierMaps()
    {
        if (empty(static::$carrierMaps)) {
            $maps = static::getShippingMapRepository()->select();
            /** @var ShippingMethodMap $map */
            foreach ($maps as $map) {
                static::$carrierMaps[$map->shopwareCarrierId] = $map->shippingMethodId;
            }
        }

        return static::$carrierMaps;
    }

    /**
     * Retrieves packlink carriers.
     *
     * @return array
     *
     * @throws \Packlink\Infrastructure\ORM\Exceptions\RepositoryNotRegisteredException
     */
    public static function getPacklinkCarriers()
    {
        if (empty(static::$packlinkCarriers)) {
            $maps = static::getCarrierMaps();
            static::$packlinkCarriers = array_keys($maps);
        }

        return static::$packlinkCarriers;
    }

    /**
     * Retrieves cached shipping costs.
     *
     * @return array
     */
    public static function getShippingCosts()
    {
        return static::$shippingCosts;
    }

    /**
     * Sets shipping costs in cache.
     *
     * @param array $costs
     */
    public static function setShippingCosts(array $costs)
    {
        static::$shippingCosts = $costs;
    }

    /**
     * Retrieves default warehouse.
     *
     * @return \Packlink\BusinessLogic\Warehouse\Warehouse | null
     */
    public static function getDefaultWarehouse()
    {
        return static::$defaultWarehouse;
    }

    /**
     * Sets default warehouse.
     *
     * @param \Packlink\BusinessLogic\Warehouse\Warehouse $warehouse
     */
    public static function setDefaultWarehouse(Warehouse $warehouse)
    {
        static::$defaultWarehouse = $warehouse;
    }

    /**
     * Retrieves default parcel.
     *
     * @return \Packlink\BusinessLogic\Http\DTO\ParcelInfo | null
     */
    public static function getDefaultParcel()
    {
        return static::$defaultParcel;
    }

    /**
     * Sets default parcel.
     *
     * @param \Packlink\BusinessLogic\Http\DTO\ParcelInfo $parcel
     */
    public static function setDefaultParcel(ParcelInfo $parcel)
    {
        static::$defaultParcel = $parcel;
    }

    /**
     * Retrieves shipping address.
     *
     * @return array
     */
    public static function getShippingAddress()
    {
        return static::$shippingAddress;
    }

    /**
     * Sets shipping address.
     *
     * @param array $shippingAddress
     */
    public static function setShippingAddress(array $shippingAddress)
    {
        static::$shippingAddress = $shippingAddress;
    }

    /**
     * Retrieves parcel items.
     *
     * @return \Packlink\BusinessLogic\Http\DTO\Package[]
     */
    public static function getParcelItems()
    {
        return static::$parcelItems;
    }

    /**
     * Sets parcel items.
     *
     * @param array $parcelItems
     */
    public static function setParcelItems(array $parcelItems)
    {
        static::$parcelItems = $parcelItems;
    }

    /**
     * Retrieves service.
     *
     * @param int $id
     *
     * @return ShippingMethod | null
     *
     * @throws \Packlink\Infrastructure\ORM\Exceptions\QueryFilterInvalidParamException
     * @throws \Packlink\Infrastructure\ORM\Exceptions\RepositoryNotRegisteredException
     */
    public static function getService($id)
    {
        if (empty(static::$services[$id])) {
            $query = new QueryFilter();
            $query->where('id', Operators::EQUALS, $id);
            static::$services[$id] = static::getShippingServicesRepository()->selectOne($query);
        }

        return static::$services[$id];
    }

    /**
     * Retrieves shipping map repository;
     *
     * @return \Packlink\Infrastructure\ORM\Interfaces\RepositoryInterface
     *
     * @throws \Packlink\Infrastructure\ORM\Exceptions\RepositoryNotRegisteredException
     */
    protected static function getShippingMapRepository()
    {
        if (static::$shippingMapRepository === null) {
            static::$shippingMapRepository = RepositoryRegistry::getRepository(ShippingMethodMap::getClassName());
        }

        return static::$shippingMapRepository;
    }

    /**
     * Retrieves shipping method repository.
     *
     * @return \Packlink\Infrastructure\ORM\Interfaces\RepositoryInterface
     * @throws \Packlink\Infrastructure\ORM\Exceptions\RepositoryNotRegisteredException
     */
    protected static function getShippingServicesRepository()
    {
        if (static::$shippingServicesRepository === null) {
            static::$shippingServicesRepository = RepositoryRegistry::getRepository(ShippingMethod::getClassName());
        }

        return static::$shippingServicesRepository;
    }
}
