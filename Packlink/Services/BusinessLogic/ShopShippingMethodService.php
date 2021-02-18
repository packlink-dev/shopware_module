<?php

namespace Packlink\Services\BusinessLogic;

use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Exception;
use Packlink\BusinessLogic\Configuration;
use Packlink\BusinessLogic\Controllers\AnalyticsController;
use Packlink\BusinessLogic\ShippingMethod\Interfaces\ShopShippingMethodService as BaseService;
use Packlink\BusinessLogic\ShippingMethod\Models\ShippingMethod;
use Packlink\Entities\ShippingMethodMap;
use Packlink\Infrastructure\ORM\Exceptions\QueryFilterInvalidParamException;
use Packlink\Infrastructure\ORM\Exceptions\RepositoryNotRegisteredException;
use Packlink\Infrastructure\ORM\QueryFilter\Operators;
use Packlink\Infrastructure\ORM\QueryFilter\QueryFilter;
use Packlink\Infrastructure\ORM\RepositoryRegistry;
use Packlink\Infrastructure\ServiceRegister;
use Packlink\Repositories\BaseRepository;
use Packlink\Utilities\Shop;
use Packlink\Utilities\Translation;
use Shopware\Components\Model\ModelEntity;
use Shopware\Models\Dispatch\Dispatch;
use Shopware\Models\Dispatch\ShippingCost;

/**
 * Class ShopShippingMethodService
 *
 * @package Packlink\Services\BusinessLogic
 */
class ShopShippingMethodService implements BaseService
{
    const OWN_CALCULATION = 3;
    const STANDARD_SHIPPING = 0;
    const ALLWAYS_CHARGE = 0;
    const DEFAULT_CARRIER = 'carrier.jpg';
    const IMG_DIR = '/Resources/views/backend/_resources/packlink/images/carriers/';
    /**
     * @var BaseRepository
     */
    protected $baseRepository;
    /**
     * @var ConfigurationService
     */
    protected $configService;

    /**
     * Adds / Activates shipping method in shop integration.
     *
     * @param ShippingMethod $shippingMethod Shipping method.
     *
     * @return bool TRUE if activation succeeded; otherwise, FALSE.
     *
     * @throws OptimisticLockException
     * @throws RepositoryNotRegisteredException
     * @throws ORMException
     */
    public function add(ShippingMethod $shippingMethod)
    {
        $carrier = $this->createShippingMethod($shippingMethod);

        $map = new ShippingMethodMap();
        $map->shopwareCarrierId = $carrier->getId();
        $map->shippingMethodId = $shippingMethod->getId();
        $map->isDropoff = $shippingMethod->isDestinationDropOff();
        $this->getBaseRepository()->save($map);

        return true;
    }

    /**
     * Updates shipping method in shop integration.
     *
     * @param ShippingMethod $shippingMethod Shipping method.
     *
     * @throws OptimisticLockException
     * @throws QueryFilterInvalidParamException
     * @throws RepositoryNotRegisteredException|ORMException
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
     * @throws QueryFilterInvalidParamException
     * @throws RepositoryNotRegisteredException
     */
    public function delete(ShippingMethod $shippingMethod)
    {
        $map = $this->getShippingMethodMap($shippingMethod);

        if ($map && $carrier = $this->getShopwareCarrier($map)) {
            try {
                $this->deleteShopwareEntity($carrier);
            } catch (Exception $e) {
                return false;
            }
        }

        $this->getBaseRepository()->delete($map);

        return true;
    }

    /**
     * Adds backup shipping method based on provided shipping method.
     *
     * @param ShippingMethod $shippingMethod
     *
     * @return bool TRUE if backup shipping method is added; otherwise, FALSE.
     *
     * @throws OptimisticLockException
     * @throws ORMException
     */
    public function addBackupShippingMethod(ShippingMethod $shippingMethod)
    {
        $shippingMethod->setTitle(Translation::get('shipping/cost'));
        $shippingMethod->setDestinationDropOff(false);
        $carrier = $this->createShippingMethod($shippingMethod);
        $this->getConfigService()->setBackupCarrierId($carrier->getId());

        return true;
    }

    /**
     * @inheritDoc
     * @throws Exception
     */
    public function getCarrierLogoFilePath($carrierName)
    {
        $pluginDir = Shopware()->Container()->getParameter('packlink.plugin_dir');

        $baseDir = $pluginDir . self::IMG_DIR;
        $image = $baseDir . strtolower(str_replace(' ', '-', $carrierName)) . '.png';

        if (!file_exists($image)) {
            $image = $baseDir . self::DEFAULT_CARRIER;
        }

        $pathResolver = Shopware()->Container()->get('theme_path_resolver');

        return $pathResolver->formatPathToUrl($image, Shop::getDefaultShop());
    }

    /**
     * Deletes backup shipping method.
     *
     * @return bool TRUE if backup shipping method is deleted; otherwise, FALSE.
     *
     * @throws OptimisticLockException
     * @throws ORMException
     */
    public function deleteBackupShippingMethod()
    {
        $id = $this->getConfigService()->getBackupCarrierId();

        /** @var Dispatch $carrier */
        if ($id !== null && $carrier = $this->getDispatchRepository()->find($id)) {
            $this->deleteShopwareEntity($carrier);
        }

        return true;
    }

    /**
     * Disables shop shipping services/carriers.
     *
     * @return boolean TRUE if operation succeeded; otherwise, false.
     * @noinspection NullPointerExceptionInspection
     */
    public function disableShopServices()
    {
        try {
            $active = $this->getNonPacklinkCarriers();
            $manager = Shopware()->Models();

            /** @var Dispatch $dispatch */
            foreach ($active as $dispatch) {
                $dispatch->setActive(false);
                $manager->persist($dispatch);
            }

            $manager->flush();

            AnalyticsController::sendOtherServicesDisabledEvent();

            return true;
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Gets non-Packlink carriers.
     *
     * @return array
     *
     * @throws RepositoryNotRegisteredException
     */
    protected function getNonPacklinkCarriers()
    {
        $query = $this->getDispatchRepository()->createQueryBuilder('d')
            ->select('d')
            ->where('d.active=1');

        if ($packlinkShippingMethods = $this->getPacklinkShippingMethods()) {
            $query->andWhere('d.id not in (' . implode(',', $packlinkShippingMethods) . ')');
        }

        return $query->getQuery()->getResult();
    }

    /**
     * Retrieves packlink shipping methods.
     *
     * @return array
     *
     * @throws RepositoryNotRegisteredException
     */
    protected function getPacklinkShippingMethods()
    {
        $repository = RepositoryRegistry::getRepository(ShippingMethodMap::getClassName());
        $maps = $repository->select();
        $methodIds = array_map(
            function (ShippingMethodMap $item) {
                return $item->shopwareCarrierId;
            },
            $maps
        );

        $backupId = $this->getConfigService()->getBackupCarrierId();

        if ($backupId !== null) {
            $methodIds[] = $backupId;
        }

        return $methodIds;
    }

    /**
     * Sets variable carrier parameters.
     *
     * @param Dispatch $carrier
     * @param ShippingMethod $shippingMethod
     *
     * @throws OptimisticLockException|ORMException
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
     * @param Dispatch $carrier
     * @param ShippingMethod $shippingMethod
     *
     * @throws OptimisticLockException
     * @throws ORMException
     */
    protected function setCarrierCost(Dispatch $carrier, ShippingMethod $shippingMethod)
    {
        $carrier->setCalculation(self::OWN_CALCULATION);
        $pricingPolicies = $shippingMethod->getPricingPolicies();
        $cost = $this->getCheapestServiceCost($shippingMethod);
        $ranges = [];

        foreach ($pricingPolicies as $policy) {
            $ranges[] = $policy->fromWeight;
            $ranges[] = $policy->fromPrice;
        }

        $ranges = array_diff($ranges, [null]);

        $this->createShopwareCost($carrier, $cost, !empty($ranges) ? min($ranges) : 0);
    }

    /**
     * Retrieves minimal cost for a shipping method.
     *
     * @param ShippingMethod $shippingMethod
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
     * @param Dispatch $carrier
     * @param float $cost
     * @param float $from
     *
     * @throws OptimisticLockException|ORMException
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
     * @param ShippingMethodMap $map
     *
     * @return Dispatch | null
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
     * @param ShippingMethod $shippingMethod
     *
     * @return ShippingMethodMap|null
     *
     * @throws QueryFilterInvalidParamException
     * @throws RepositoryNotRegisteredException
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
     * @return BaseRepository
     *
     * @throws RepositoryNotRegisteredException
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
     * @throws OptimisticLockException|ORMException
     */
    protected function persistShopwareEntity(ModelEntity $entity)
    {
        Shopware()->Models()->persist($entity);
        Shopware()->Models()->flush($entity);
    }

    /**
     * Deletes shopware entity;
     *
     * @param ModelEntity $entity
     *
     * @throws OptimisticLockException|ORMException
     */
    protected function deleteShopwareEntity(ModelEntity $entity)
    {
        Shopware()->Models()->remove($entity);
        Shopware()->Models()->flush();
    }

    /**
     * Creates shipping method.
     *
     * @param ShippingMethod $shippingMethod
     *
     * @return Dispatch
     *
     * @throws OptimisticLockException|ORMException
     */
    protected function createShippingMethod(ShippingMethod $shippingMethod)
    {
        $carrier = new Dispatch();

        if ($shippingMethod->isShipToAllCountries()) {
            $countries = $this->getDispatchRepository()->getCountryQuery()->getResult();
        } else {
            $countries = $this->fetchSelectedCountries($shippingMethod);
        }

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

        return $carrier;
    }

    /**
     * @param ShippingMethod $shippingMethod
     * @return int|mixed|string
     */
    protected function fetchSelectedCountries(ShippingMethod $shippingMethod)
    {
        $countries = implode(', ', $shippingMethod->getShippingCountries());

        return $this->getDispatchRepository()->getCountryQueryBuilder()
            ->where("countryname IN ($countries)")->getQuery()->getResult();
    }

    /**
     * Retrieves configuration service.
     *
     * @return ConfigurationService
     */
    protected function getConfigService()
    {
        if ($this->configService === null) {
            $this->configService = ServiceRegister::getService(Configuration::CLASS_NAME);
        }

        return $this->configService;
    }
}
