<?php

namespace Packlink\Services\BusinessLogic;

use Doctrine\ORM\OptimisticLockException;
use Logeecom\Infrastructure\ORM\QueryFilter\Operators;
use Logeecom\Infrastructure\ORM\QueryFilter\QueryFilter;
use Logeecom\Infrastructure\ORM\RepositoryRegistry;
use Packlink\BusinessLogic\ShippingMethod\Interfaces\ShopShippingMethodService as BaseService;
use Packlink\BusinessLogic\ShippingMethod\Models\ShippingMethod;
use Packlink\Entities\ShippingMethodMap;
use Shopware\Components\Model\ModelEntity;
use Shopware\Models\Dispatch\Dispatch;
use Shopware\Models\Dispatch\ShippingCost;

class ShopShippingMethodService implements BaseService
{
    const PRICE = 1;
    const WEIGHT = 0;
    const STANDARD_SHIPPING = 0;
    const ALLWAYS_CHARGE = 0;
    /**
     * @var \Packlink\Repositories\BaseRepository
     */
    protected $baseRepository;

    /**
     * Adds / Activates shipping method in shop integration.
     *
     * @param ShippingMethod $shippingMethod Shipping method.
     *
     * @return bool TRUE if activation succeeded; otherwise, FALSE.
     *
     * @throws \Doctrine\ORM\OptimisticLockException
     * @throws \Logeecom\Infrastructure\ORM\Exceptions\RepositoryNotRegisteredException
     */
    public function add(ShippingMethod $shippingMethod)
    {
        $carrier = new Dispatch();

        $countries = $this->getDispatchRepository()->getCountryQuery()->getResult();
        foreach ($countries as $country) {
            $carrier->getCountries()->add($country);
        }

        $payments = $this->getDispatchRepository()->getPaymentQuery()->getResult();
        foreach ($payments as $payment) {
            $carrier->getPayments()->add($payment);
        }

        // TODO set carrier tracking url

        $carrier->setDescription('');
        $carrier->setComment('');
        $carrier->setPosition(0);
        $carrier->setActive(true);
        $carrier->setMultiShopId(null);
        $carrier->setCustomerGroupId(null);
        $carrier->setShippingFree(null);
        $carrier->setType(self::STANDARD_SHIPPING);
        $carrier->setSurchargeCalculation(self::ALLWAYS_CHARGE);
        $carrier->setCalculation(0);
        $carrier->setTaxCalculation(0);
        $carrier->setBindLastStock(0);

        $this->setVariableCarrierParameters($carrier, $shippingMethod);

        $map = new ShippingMethodMap();
        $map->shopwareCarrierId = $carrier->getId();
        $map->shippingMethodId = $shippingMethod->getId();
        $this->getBaseRepository()->save($map);

        return true;
    }

    /**
     * Updates shipping method in shop integration.
     *
     * @param ShippingMethod $shippingMethod Shipping method.
     *
     * @throws \Doctrine\ORM\OptimisticLockException
     * @throws \Logeecom\Infrastructure\ORM\Exceptions\QueryFilterInvalidParamException
     * @throws \Logeecom\Infrastructure\ORM\Exceptions\RepositoryNotRegisteredException
     */
    public function update(ShippingMethod $shippingMethod)
    {
        $map = $this->getShippingMethodMap($shippingMethod);

        if ($map && $carrier = $this->getShopwareCarrier($map)) {
            $this->getDispatchRepository()->getPurgeShippingCostsMatrixQuery($carrier)->execute();
            $this->setVariableCarrierParameters($carrier, $shippingMethod);
        }
    }

    /**
     * Deletes shipping method in shop integration.
     *
     * @param ShippingMethod $shippingMethod Shipping method.
     *
     * @return bool TRUE if deletion succeeded; otherwise, FALSE.
     *
     * @throws \Logeecom\Infrastructure\ORM\Exceptions\QueryFilterInvalidParamException
     * @throws \Logeecom\Infrastructure\ORM\Exceptions\RepositoryNotRegisteredException
     */
    public function delete(ShippingMethod $shippingMethod)
    {
        $map = $this->getShippingMethodMap($shippingMethod);

        if ($map && $carrier = $this->getShopwareCarrier($map)) {
            try {
                $this->deleteShopwareEntity($carrier);
            } catch (OptimisticLockException $e) {
                return false;
            }
        }

        $this->getBaseRepository()->delete($map);

        return true;
    }

    /**
     * Sets variable carrier parameters.
     *
     * @param \Shopware\Models\Dispatch\Dispatch $carrier
     * @param \Packlink\BusinessLogic\ShippingMethod\Models\ShippingMethod $shippingMethod
     *
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    protected function setVariableCarrierParameters(Dispatch $carrier, ShippingMethod $shippingMethod)
    {
        $carrier->setName($shippingMethod->getTitle());

        if ($shippingMethod->getTaxClass()) {
            $carrier->setTaxCalculation($shippingMethod->getTaxClass());
        }

        $this->persistShopwareEntity($carrier);

        $this->setCarrierCost($carrier, $shippingMethod);

        Shopware()->Models()->flush($carrier);
    }

    /**
     * Sets carrier price.
     *
     * @param \Shopware\Models\Dispatch\Dispatch $carrier
     * @param \Packlink\BusinessLogic\ShippingMethod\Models\ShippingMethod $shippingMethod
     *
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    protected function setCarrierCost(Dispatch $carrier, ShippingMethod $shippingMethod)
    {
        switch ($shippingMethod->getPricingPolicy()) {
            case ShippingMethod::PRICING_POLICY_PACKLINK:
            case ShippingMethod::PRICING_POLICY_PERCENT:
                $this->setPacklinkPrice($carrier, $shippingMethod);
                break;
            case ShippingMethod::PRICING_POLICY_FIXED_PRICE_BY_WEIGHT:
                $this->setWeightPrice($carrier, $shippingMethod);
                break;
            case ShippingMethod::PRICING_POLICY_FIXED_PRICE_BY_VALUE:
                $this->setValuePrice($carrier, $shippingMethod);
                break;
        }
    }

    /**
     * Creates cost based on packlink price.
     *
     * @param \Shopware\Models\Dispatch\Dispatch $carrier
     * @param \Packlink\BusinessLogic\ShippingMethod\Models\ShippingMethod $shippingMethod
     *
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    protected function setPacklinkPrice(Dispatch $carrier, ShippingMethod $shippingMethod)
    {
        $carrier->setCalculation(self::PRICE);
        $cost = $this->getCheapestServiceCost($shippingMethod);

        if ($shippingMethod->getPricingPolicy() === ShippingMethod::PRICING_POLICY_PERCENT) {
            $part = $cost * ($shippingMethod->getPercentPricePolicy()->amount / 100);
            $cost += $shippingMethod->getPercentPricePolicy()->increase ? $part : (-1 * $part);
        }

        $this->createShopwareCost($carrier, $cost);
    }

    /**
     * Creates cost based on weight.
     *
     * @param \Shopware\Models\Dispatch\Dispatch $carrier
     * @param \Packlink\BusinessLogic\ShippingMethod\Models\ShippingMethod $shippingMethod
     *
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    protected function setWeightPrice(Dispatch $carrier, ShippingMethod $shippingMethod)
    {
        $carrier->setCalculation(self::WEIGHT);
        foreach ($shippingMethod->getFixedPriceByWeightPolicy() as $policy) {
            $this->createShopwareCost($carrier, $policy->amount, $policy->from);
        }
    }

    /**
     * Creates cost based on value
     *
     * @param \Shopware\Models\Dispatch\Dispatch $carrier
     * @param \Packlink\BusinessLogic\ShippingMethod\Models\ShippingMethod $shippingMethod
     *
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    protected function setValuePrice(Dispatch $carrier, ShippingMethod $shippingMethod)
    {
        $carrier->setCalculation(self::PRICE);
        foreach ($shippingMethod->getFixedPriceByValuePolicy() as $policy) {
            $this->createShopwareCost($carrier, $policy->amount, $policy->from);
        }
    }

    /**
     * Retrieves minimal cost for a shipping method.
     *
     * @param \Packlink\BusinessLogic\ShippingMethod\Models\ShippingMethod $shippingMethod
     *
     * @return float
     */
    protected function getCheapestServiceCost(ShippingMethod $shippingMethod)
    {
        $minCost = PHP_INT_MAX;

        foreach ($shippingMethod->getShippingServices() as $service) {
            if ($service->basePrice <= $minCost) {
                $minCost = $service->basePrice;
            }
        }

        return $minCost;
    }

    /**
     * Creates shopware cost.
     *
     * @param \Shopware\Models\Dispatch\Dispatch $carrier
     * @param float $cost
     * @param float $from
     *
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    protected function createShopwareCost(Dispatch $carrier, $cost, $from = 0.0)
    {
        $shopwareCost = new ShippingCost();
        $shopwareCost->setValue($cost);
        $shopwareCost->setFrom($from);
        $shopwareCost->setFactor(0);
        $shopwareCost->setDispatch($carrier);

        $this->persistShopwareEntity($shopwareCost);
    }

    /**
     * Retrieves shopware carrier by shipping method.
     *
     * @param \Packlink\Entities\ShippingMethodMap $map
     *
     * @return \Shopware\Models\Dispatch\Dispatch | null
     *
     * @throws \Logeecom\Infrastructure\ORM\Exceptions\QueryFilterInvalidParamException
     * @throws \Logeecom\Infrastructure\ORM\Exceptions\RepositoryNotRegisteredException
     */
    protected function getShopwareCarrier(ShippingMethodMap $map)
    {
        /** @var Dispatch | null $entity */
        $entity = $this->getDispatchRepository()->find($map->shopwareCarrierId);

        return $entity;
    }

    /**
     * Retrieves shipping method map.
     *
     * @param \Packlink\BusinessLogic\ShippingMethod\Models\ShippingMethod $shippingMethod
     *
     * @return \Packlink\Entities\ShippingMethodMap|null
     *
     * @throws \Logeecom\Infrastructure\ORM\Exceptions\QueryFilterInvalidParamException
     * @throws \Logeecom\Infrastructure\ORM\Exceptions\RepositoryNotRegisteredException
     */
    protected function getShippingMethodMap(ShippingMethod $shippingMethod)
    {
        $filter = new QueryFilter();
        $filter->where('shippingMethodId', Operators::EQUALS, $shippingMethod->getId());

        /** @var ShippingMethodMap | null $entity */
        $entity = $this->getBaseRepository()->selectOne($filter);

        return $entity;
    }

    /**
     * Retrieves Dispatch repository.
     *
     * @return \Shopware\Models\Dispatch\Repository
     */
    protected function getDispatchRepository()
    {
        return Shopware()->Models()->getRepository(Dispatch::class);
    }

    /**
     * Retrieves base repository.
     *
     * @return \Packlink\Repositories\BaseRepository
     *
     * @throws \Logeecom\Infrastructure\ORM\Exceptions\RepositoryNotRegisteredException
     */
    protected function getBaseRepository()
    {
        if ($this->baseRepository === null) {
            $this->baseRepository = RepositoryRegistry::getRepository(ShippingMethodMap::getClassName());
        }

        return $this->baseRepository;
    }

    /**
     * Persists shopware entity.
     *
     * @param ModelEntity $entity
     *
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    protected function persistShopwareEntity(ModelEntity $entity)
    {
        Shopware()->Models()->persist($entity);
        Shopware()->Models()->flush($entity);
    }

    /**
     * Deletes shopware entity;
     *
     * @param \Shopware\Components\Model\ModelEntity $entity
     *
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    protected function deleteShopwareEntity(ModelEntity $entity)
    {
        Shopware()->Models()->remove($entity);
        Shopware()->Models()->flush();
    }
}