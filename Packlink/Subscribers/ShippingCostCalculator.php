<?php

namespace Packlink\Subscribers;

use Doctrine\DBAL\Types\Type;
use Enlight\Event\SubscriberInterface;
use Enlight_Hook_HookArgs;
use Exception;
use Logeecom\Infrastructure\Logger\Logger;
use Logeecom\Infrastructure\ServiceRegister;
use Packlink\Bootstrap\Bootstrap;
use Packlink\BusinessLogic\Configuration;
use Packlink\BusinessLogic\Http\DTO\Package;
use Packlink\BusinessLogic\ShippingMethod\ShippingMethodService;
use Packlink\Exceptions\FailedToRetrieveCheckoutAddressException;
use Packlink\Exceptions\FailedToRetrieveDefaultUserAddressException;
use Packlink\Utilities\Cache;
use Shopware\Models\Customer\Address;
use Shopware\Models\Dispatch\Dispatch;
use Shopware\Models\Dispatch\ShippingCost;

class ShippingCostCalculator implements SubscriberInterface
{
    protected static $cachedCosts = false;
    protected static $updatedCarriers = [];
    /** @var \Packlink\Services\BusinessLogic\ConfigurationService */
    protected $configService;
    /** @var ShippingMethodService */
    protected $shippingMethodService;

    /**
     * @inheritDoc
     */
    public static function getSubscribedEvents()
    {
        return [
            'sAdmin::sGetPremiumShippingcosts::before' => 'onBeforeSGetPremiumShippingcosts',
        ];
    }

    /**
     * Handles sAdmin::sGetPremiumShippingcosts::before hook.
     *
     * @param \Enlight_Hook_HookArgs $args
     *
     * @throws \Logeecom\Infrastructure\ORM\Exceptions\RepositoryNotRegisteredException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function onBeforeSGetPremiumShippingcosts(Enlight_Hook_HookArgs $args)
    {
        Bootstrap::init();

        if (!$this->shouldHandle()) {
            return;
        }

        try {
            $shippingCosts = $this->getShippingCosts();
        } catch (Exception $e) {
            Logger::logError("Failed to calculate shipping cost because: [{$e->getMessage()}].");

            return;
        }

        $carrierId = (int)Shopware()->Session()->get('sDispatch');
        $maps = Cache::getCarrierMaps();

        if (isset($maps[$carrierId], $shippingCosts[$maps[$carrierId]])) {
            $cost = (float)$shippingCosts[$maps[$carrierId]];
            $this->updateCost($carrierId, $cost);
            static::$updatedCarriers[] = $carrierId;
        }
    }

    /**
     * Checks whether hook should be handled.
     *
     * @return bool
     * @throws \Logeecom\Infrastructure\ORM\Exceptions\RepositoryNotRegisteredException
     */
    protected function shouldHandle()
    {
        $shippingId = Shopware()->Session()->get('checkoutShippingAddressId');
        $userId = Shopware()->Session()->get('sUserId');
        $sessionId = Shopware()->Session()->get('sessionId');
        $carrierId = Shopware()->Session()->get('sDispatch');

        return !empty($sessionId)
            && (!empty($shippingId) || !empty($userId))
            && !empty($carrierId)
            && $this->isPacklinkCarrier((int)$carrierId)
            && !$this->isCarrierUpdated((int)$carrierId);
    }

    /**
     * Checks whether carrier is packlink carrier.
     *
     * @param int $carrierId
     *
     * @return bool
     * @throws \Logeecom\Infrastructure\ORM\Exceptions\RepositoryNotRegisteredException
     */
    protected function isPacklinkCarrier($carrierId)
    {
        $packlinkCarriers = Cache::getPacklinkCarriers();

        return in_array($carrierId, $packlinkCarriers, true);
    }

    /**
     * Checks whether carrier is already updated.
     *
     * @param int $carrierId
     *
     * @return bool
     */
    protected function isCarrierUpdated($carrierId)
    {
        return in_array($carrierId, static::$updatedCarriers, true);
    }

    /**
     * Retrieves shipping costs.
     *
     * @return array
     *
     * @throws \Packlink\Exceptions\FailedToRetrieveCheckoutAddressException
     * @throws \Packlink\Exceptions\FailedToRetrieveDefaultUserAddressException
     */
    protected function getShippingCosts()
    {
        if (!static::$cachedCosts) {
            $costs = $this->calculateShippingCosts();
            Cache::setShippingCosts($costs);
            static::$cachedCosts = true;
        }

        return Cache::getShippingCosts();
    }

    /**
     * Calculates shipping costs.
     *
     * @return array
     *
     * @throws \Packlink\Exceptions\FailedToRetrieveCheckoutAddressException
     * @throws \Packlink\Exceptions\FailedToRetrieveDefaultUserAddressException
     * @throws \Exception
     */
    protected function calculateShippingCosts()
    {
        $sourceAddress = $this->getSourceAddress();
        $shippingAddress = $this->getShippingAddress();
        $parcelItems = $this->getParcelItems();
        $amount = $this->getTotalAmount();
        $costs = $this->getShippingMethodService()->getShippingCosts(
            $sourceAddress['countryCode'],
            $sourceAddress['postalCode'],
            $shippingAddress['countryCode'],
            $shippingAddress['postalCode'],
            $parcelItems,
            $amount
        );

        return $costs;
    }

    /**
     * Retrieves default warehouse.
     *
     * @return \Packlink\BusinessLogic\Http\DTO\Warehouse
     */
    protected function getDefaultWarehouse()
    {
        if (($warehouse = Cache::getDefaultWarehouse()) === null) {
            $warehouse = $this->getConfigService()->getDefaultWarehouse();
            /** @noinspection NullPointerExceptionInspection */
            Cache::setDefaultWarehouse($warehouse);
        }

        return $warehouse;
    }

    /**
     * Retrieves default parcel.
     *
     * @return \Packlink\BusinessLogic\Http\DTO\ParcelInfo
     */
    protected function getDefaultParcel()
    {
        if (($parcel = Cache::getDefaultParcel()) === null) {
            $parcel = $this->getConfigService()->getDefaultParcel();
            /** @noinspection NullPointerExceptionInspection */
            Cache::setDefaultParcel($parcel);
        }

        return $parcel;
    }

    /**
     * Retrieves shipping address.
     *
     * @return array
     *
     * @throws \Packlink\Exceptions\FailedToRetrieveCheckoutAddressException
     * @throws \Packlink\Exceptions\FailedToRetrieveDefaultUserAddressException
     * @throws \Exception
     */
    protected function getShippingAddress()
    {
        if (empty($shippingAddress = Cache::getShippingAddress())) {
            $shippingId = Shopware()->Session()->get('checkoutShippingAddressId');
            $userId = Shopware()->Session()->get('sUserId');

            if (!empty($shippingId)) {
                try {
                    $shippingAddress = $this->getCheckoutShippingAddress((int)$shippingId);
                } catch (FailedToRetrieveCheckoutAddressException $e) {
                    $shippingAddress = $this->getAddress((int)$shippingId);
                }
            } else {
                $shippingAddress = $this->getDefaultUserAddress((int)$userId);
            }

            Cache::setShippingAddress($shippingAddress);
        }

        return $shippingAddress;
    }

    /**
     * Retrieves config service.
     *
     * @return \Packlink\Services\BusinessLogic\ConfigurationService
     */
    protected function getConfigService()
    {
        if ($this->configService === null) {
            $this->configService = ServiceRegister::getService(Configuration::CLASS_NAME);
        }

        return $this->configService;
    }

    /**
     * Retrieves shipping address.
     *
     * @param int $addressId
     *
     * @return array
     * @throws \Packlink\Exceptions\FailedToRetrieveCheckoutAddressException
     * @throws \Exception
     */
    protected function getCheckoutShippingAddress($addressId)
    {
        /** @var \Doctrine\DBAL\Connection $connection */
        $connection = Shopware()->Container()->get('dbal_connection');
        $sql = 'SELECT a.zipcode, c.countryiso 
                FROM s_user_shippingaddress as a
                LEFT JOIN s_core_countries as c on c.id = a.countryId
                WHERE a.id=?;';

        $rawData = $connection->fetchAll($sql, [$addressId], [Type::INTEGER]);
        if (empty($rawData[0]['zipcode']) || empty($rawData[0]['countryiso'])) {
            throw new FailedToRetrieveCheckoutAddressException("Failed to retrieve address with id [$addressId].");
        }

        return [
            'countryCode' => $rawData[0]['countryiso'],
            'postalCode' => $rawData[0]['zipcode'],
        ];
    }

    /**
     * Retrieves address.
     *
     * @param $addressId
     *
     * @return array
     *
     * @throws \Packlink\Exceptions\FailedToRetrieveCheckoutAddressException
     */
    protected function getAddress($addressId)
    {
        $repository = Shopware()->Models()->getRepository(Address::class);
        /** @var Address $address */
        $address = $repository->find($addressId);

        if ($address === null || empty($address->getZipcode()) || empty($address->getCountry()->getIso())) {
            throw new FailedToRetrieveCheckoutAddressException("Address [{$addressId}] can not be retrieved.");
        }

        return [
            'countryCode' => $address->getCountry()->getIso(),
            'postalCode' => $address->getZipcode(),
        ];
    }

    /**
     * Retrieves default user address.
     *
     * @param int $userId
     *
     * @return array
     *
     * @throws \Packlink\Exceptions\FailedToRetrieveDefaultUserAddressException
     */
    protected function getDefaultUserAddress($userId)
    {
        $repository = Shopware()->Models()->getRepository(Address::class);
        /** @var Address[] $address */
        $address = $repository->findBy(['customer' => $userId]);

        if (empty($address[0]) || empty($address[0]->getZipcode()) || empty($address[0]->getCountry()->getIso())) {
            throw new FailedToRetrieveDefaultUserAddressException("Address for user [$userId] can not be retrieved.");
        }

        return [
            'countryCode' => $address[0]->getCountry()->getIso(),
            'postalCode' => $address[0]->getZipcode(),
        ];
    }

    /**
     * Retrieves list of packages.
     *
     * @return \Packlink\BusinessLogic\Http\DTO\Package[]
     *
     * @throws \Exception
     */
    protected function getParcelItems()
    {
        if (empty($parcelItems = Cache::getParcelItems())) {
            $parcelItems = $this->getCartItems();
            if (empty($parcelItems)) {
                $parcelItems[] = Package::defaultPackage();
            }

            Cache::setParcelItems($parcelItems);
        }

        return $parcelItems;
    }

    /**
     * Retrieves cart items.
     *
     * @return Package[]
     *
     * @throws \Exception
     */
    protected function getCartItems()
    {
        $result = [];
        $sessionId = Shopware()->Session()->get('sessionId');
        if (empty($sessionId)) {
            return $result;
        }

        /** @var \Doctrine\DBAL\Connection $connection */
        $connection = Shopware()->Container()->get('dbal_connection');
        $sql = 'SELECT b.quantity,  a.weight, a.width, a.length, a.height 
                FROM s_order_basket as b
                LEFT JOIN s_articles_details as a on a.ordernumber = b.ordernumber and a.articleID = b.articleID
                WHERE b.sessionID=?;';

        $rawData = $connection->fetchAll($sql, [$sessionId]);
        $defaultParcel = $this->getDefaultParcel();
        foreach ($rawData as $item) {
            $quantity = !empty($item['quantity']) ? (int)$item['quantity'] : 0;

            for ($i = 0; $i < $quantity; $i++) {
                $package = new Package();
                $package->weight = !empty($item['weight']) ? (float)$item['weight'] : $defaultParcel->weight;
                $package->width = !empty($item['width']) ? (int)$item['width'] : $defaultParcel->width;
                $package->length = !empty($item['length']) ? (int)$item['length'] : $defaultParcel->length;
                $package->height = !empty($item['height']) ? (int)$item['height'] : $defaultParcel->height;

                $result[] = $package;
            }
        }

        return $result;
    }

    /**
     * Retrieves source address.
     *
     * @return array
     */
    protected function getSourceAddress()
    {
        $warehouse = $this->getDefaultWarehouse();

        return [
            'countryCode' => $warehouse->country,
            'postalCode' => $warehouse->postalCode,
        ];
    }

    /**
     * Retrieves basket total amount.
     *
     * @return float
     * @throws \Exception
     */
    protected function getTotalAmount()
    {
        $result = 0.0;

        $sessionId = Shopware()->Session()->get('sessionId');
        if (empty($sessionId)) {
            return $result;
        }

        /** @var \Doctrine\DBAL\Connection $connection */
        $connection = Shopware()->Container()->get('dbal_connection');
        $sql = 'SELECT sum(b.price * b.quantity)  as total
                FROM s_order_basket AS b
                WHERE b.sessionID=?
                GROUP BY b.sessionID;';

        $raw = $connection->fetchAll($sql, [$sessionId]);
        $result = !empty($raw[0]['total']) ? (float)$raw[0]['total'] : 0.0;

        return $result;
    }

    /**
     * Updates shipping cost.
     *
     * @param int $carrierId
     * @param float $cost
     *
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    protected function updateCost($carrierId, $cost)
    {
        /** @var \Shopware\Models\Dispatch\Repository $carrierRepository */
        $carrierRepository = Shopware()->Models()->getRepository(Dispatch::class);
        /** @var Dispatch $carrier */
        if (($carrier = $carrierRepository->find($carrierId)) !== null) {
            $carrierRepository->getPurgeShippingCostsMatrixQuery($carrierId)->execute();
            $carrier->setCalculation(1);
            $shippingCost = new ShippingCost();
            $shippingCost->setFactor(0);
            $shippingCost->setValue($cost);
            $shippingCost->setFrom(0);
            $shippingCost->setDispatch($carrier);

            Shopware()->Models()->persist($carrier);
            Shopware()->Models()->persist($shippingCost);
            Shopware()->Models()->flush();
        }
    }

    /**
     * Retrieves shipping method service.
     *
     * @return \Packlink\BusinessLogic\ShippingMethod\ShippingMethodService
     */
    protected function getShippingMethodService()
    {
        if ($this->shippingMethodService === null) {
            $this->shippingMethodService = ServiceRegister::getService(ShippingMethodService::CLASS_NAME);
        }

        return $this->shippingMethodService;
    }
}