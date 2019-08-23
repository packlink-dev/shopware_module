<?php

namespace Packlink\Services\BusinessLogic;

use Doctrine\DBAL\Types\Type;
use Logeecom\Infrastructure\ORM\RepositoryRegistry;
use Logeecom\Infrastructure\ServiceRegister;
use Packlink\BusinessLogic\Configuration;
use Packlink\BusinessLogic\Http\DTO\Package;
use Packlink\BusinessLogic\ShippingMethod\Models\ShippingMethod;
use Packlink\BusinessLogic\ShippingMethod\ShippingCostCalculator;
use Packlink\Exceptions\FailedToRetrieveCheckoutAddressException;
use Packlink\Exceptions\FailedToRetrieveDefaultUserAddressException;
use Packlink\Utilities\Cache;
use Shopware\Models\Customer\Address;

class CheckoutService
{
    protected static $cachedCosts = false;
    /**
     * @var \Packlink\Services\BusinessLogic\ConfigurationService
     */
    protected $configService;
    /**
     * @var \Logeecom\Infrastructure\ORM\Interfaces\RepositoryInterface
     */
    protected $shippingMethodRepository;

    /**
     * Retrieves shipping costs.
     *
     * @param int $userId
     * @param string $sessionId
     * @param int | null $shippingAddressId
     *
     * @return array
     *
     * @throws \Packlink\Exceptions\FailedToRetrieveCheckoutAddressException
     * @throws \Packlink\Exceptions\FailedToRetrieveDefaultUserAddressException
     */
    public function getShippingCosts($userId, $sessionId, $shippingAddressId = null)
    {
        if (!static::$cachedCosts) {
            $costs = $this->calculateShippingCosts($userId, $sessionId, $shippingAddressId);
            Cache::setShippingCosts($costs);
            static::$cachedCosts = true;
        }

        return Cache::getShippingCosts();
    }

    /**
     * Calculates shipping costs.
     *
     * @param int $userId
     * @param string $sessionId
     * @param int | null $shippingAddressId
     *
     * @return array
     *
     * @throws \Packlink\Exceptions\FailedToRetrieveCheckoutAddressException
     * @throws \Packlink\Exceptions\FailedToRetrieveDefaultUserAddressException
     * @throws \Exception
     */
    protected function calculateShippingCosts($userId, $sessionId, $shippingAddressId = null)
    {
        $sourceAddress = $this->getSourceAddress();
        $shippingAddress = $this->getShippingAddress($userId, $shippingAddressId);
        $parcelItems = $this->getParcelItems($sessionId);
        $amount = $this->getTotalAmount($sessionId);
        /** @var ShippingMethod[] $shippingMethods */
        $shippingMethods = $this->getShippingMethodRepository()->select();
        $costs = ShippingCostCalculator::getShippingCosts(
            $shippingMethods,
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
     * @param int $userId
     * @param int | null $shippingId
     *
     * @return array
     *
     * @throws \Packlink\Exceptions\FailedToRetrieveCheckoutAddressException
     * @throws \Packlink\Exceptions\FailedToRetrieveDefaultUserAddressException
     * @throws \Exception
     */
    protected function getShippingAddress($userId, $shippingId = null)
    {
        if (empty($shippingAddress = Cache::getShippingAddress())) {
            if ($shippingId !== null) {
                try {
                    $shippingAddress = $this->getCheckoutShippingAddress($shippingId);
                } catch (FailedToRetrieveCheckoutAddressException $e) {
                    $shippingAddress = $this->getAddress($shippingId);
                }
            } else {
                $shippingAddress = $this->getDefaultUserAddress($userId);
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
     * @param string $sessionId
     *
     * @return \Packlink\BusinessLogic\Http\DTO\Package[]
     *
     * @throws \Exception
     */
    protected function getParcelItems($sessionId)
    {
        if (empty($parcelItems = Cache::getParcelItems())) {
            $parcelItems = $this->getCartItems($sessionId);
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
     * @param string $sessionId
     *
     * @return Package[]
     *
     * @throws \Exception
     */
    protected function getCartItems($sessionId)
    {
        $result = [];
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
     * @param string $sessionId
     *
     * @return float
     * @throws \Exception
     */
    protected function getTotalAmount($sessionId)
    {
        $result = 0.0;

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
     * Retrieves shipping method repository.
     *
     * @return \Logeecom\Infrastructure\ORM\Interfaces\RepositoryInterface
     * @throws \Logeecom\Infrastructure\ORM\Exceptions\RepositoryNotRegisteredException
     */
    protected function getShippingMethodRepository()
    {
        if ($this->shippingMethodRepository === null) {
            $this->shippingMethodRepository = RepositoryRegistry::getRepository(ShippingMethod::getClassName());
        }

        return $this->shippingMethodRepository;
    }
}