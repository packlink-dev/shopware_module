<?php

namespace Packlink\Repositories;

use Logeecom\Infrastructure\ORM\QueryFilter\Operators;
use Logeecom\Infrastructure\ORM\QueryFilter\QueryFilter;
use Logeecom\Infrastructure\ORM\RepositoryRegistry;
use Logeecom\Infrastructure\ServiceRegister;
use Packlink\BusinessLogic\Configuration;
use Packlink\BusinessLogic\Http\DTO\ParcelInfo;
use Packlink\BusinessLogic\Http\DTO\Shipment;
use Packlink\BusinessLogic\Http\DTO\Tracking;
use Packlink\BusinessLogic\Order\Exceptions\OrderNotFound;
use Packlink\BusinessLogic\Order\Interfaces\OrderRepository as BaseOrderRepository;
use Packlink\BusinessLogic\Order\Models\OrderShipmentDetails;
use Packlink\BusinessLogic\Order\Objects\Address;
use Packlink\BusinessLogic\Order\Objects\Item;
use Packlink\BusinessLogic\Order\Objects\Order;
use Packlink\BusinessLogic\ShippingMethod\Utility\ShipmentStatus;
use Packlink\Entities\OrderDropoffMap;
use Packlink\Entities\ShippingMethodMap;
use Shopware\Models\Article\Article;

class OrderRepository implements BaseOrderRepository
{
    /**
     * @var \Logeecom\Infrastructure\ORM\Interfaces\RepositoryInterface
     */
    protected $orderDetailsRepository;
    /**
     * @var \Packlink\BusinessLogic\Configuration
     */
    protected $configService;
    /**
     * @var \Logeecom\Infrastructure\ORM\Interfaces\RepositoryInterface
     */
    protected $orderDropoffRepository;
    /**
     * @var \Logeecom\Infrastructure\ORM\Interfaces\RepositoryInterface
     */
    protected $shippingMethodMapRepository;

    /**
     * Returns shipment references of the orders that have not yet been completed.
     *
     * @return array Array of shipment references.
     *
     * @throws \Logeecom\Infrastructure\ORM\Exceptions\QueryFilterInvalidParamException
     * @throws \Logeecom\Infrastructure\ORM\Exceptions\RepositoryNotRegisteredException
     */
    public function getIncompleteOrderReferences()
    {
        $filter = new QueryFilter();
        $filter->where('status', Operators::NOT_EQUALS, ShipmentStatus::STATUS_DELIVERED);
        $orders = $this->getOrderDetailsRepository()->select($filter);

        $result = [];

        /** @var OrderShipmentDetails $order */
        foreach ($orders as $order) {
            if ($order->getReference() !== null) {
                $result[] = $order->getReference();
            }
        }

        return $result;
    }

    /**
     * Fetches and returns system order by its unique identifier.
     *
     * @param string $orderId $orderId Unique order id.
     *
     * @return Order Order object.
     *
     * @throws \Logeecom\Infrastructure\ORM\Exceptions\QueryFilterInvalidParamException
     * @throws \Logeecom\Infrastructure\ORM\Exceptions\RepositoryNotRegisteredException
     * @throws \Packlink\BusinessLogic\Order\Exceptions\OrderNotFound When order with provided id is not found.
     */
    public function getOrderAndShippingData($orderId)
    {
        /** @var \Shopware\Models\Order\Order $sourceOrder */
        $sourceOrder = $this->getShopwareOrderRepository()->find($orderId);

        if ($sourceOrder === null) {
            throw new OrderNotFound("Order with id [$orderId] not found.");
        }

        $order = new Order();

        $order->setId($sourceOrder->getId());
        $order->setOrderNumber($sourceOrder->getNumber());
        $order->setCustomerId($sourceOrder->getCustomer()->getId());
        $order->setCurrency($sourceOrder->getCurrency());
        $order->setTotalPrice($sourceOrder->getInvoiceAmount());
        $order->setBasePrice($sourceOrder->getInvoiceAmountNet());

        $this->setDropoffId($order, $orderId);
        $dispatch = $sourceOrder->getDispatch();
        if ($dispatch) {
            $this->setShippingMethodId($order, $dispatch->getId());
        }
        $order->setShippingAddress($this->getOrderAddress($sourceOrder));

        $order->setItems($this->getOrderItems($sourceOrder));

        return $order;
    }

    /**
     * Sets order packlink reference number.
     *
     * @param string $orderId Unique order id.
     * @param string $shipmentReference Packlink shipment reference.
     *
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     * @throws \Doctrine\ORM\TransactionRequiredException
     * @throws \Logeecom\Infrastructure\ORM\Exceptions\QueryFilterInvalidParamException
     * @throws \Logeecom\Infrastructure\ORM\Exceptions\RepositoryNotRegisteredException
     * @throws \Packlink\BusinessLogic\Order\Exceptions\OrderNotFound
     */
    public function setReference($orderId, $shipmentReference)
    {
        $sourceOrder = $this->getShopwareOrderRepository()->find($orderId);

        if ($sourceOrder === null) {
            throw new OrderNotFound("Source order with id [$orderId] not found.");
        }

        $orderDetails = $this->getOrderDetailsById($orderId);

        if ($orderDetails === null) {
            $orderDetails = new OrderShipmentDetails();
            $orderDetails->setOrderId($orderId);
            $orderDetails->setReference($shipmentReference);
            $this->getOrderDetailsRepository()->save($orderDetails);
        } else {
            $orderDetails->setReference($shipmentReference);
            $this->getOrderDetailsRepository()->update($orderDetails);
        }

        $this->setShippingStatusByReference($shipmentReference, ShipmentStatus::STATUS_PENDING);
    }

    /**
     * Sets order packlink shipping labels to an order by shipment reference.
     *
     * @param string $shipmentReference Packlink shipment reference.
     * @param string[] $labels Packlink shipping labels.
     *
     * @throws \Logeecom\Infrastructure\ORM\Exceptions\QueryFilterInvalidParamException
     * @throws \Logeecom\Infrastructure\ORM\Exceptions\RepositoryNotRegisteredException
     * @throws \Packlink\BusinessLogic\Order\Exceptions\OrderNotFound When order with provided reference is not found.
     */
    public function setLabelsByReference($shipmentReference, array $labels)
    {
        $details = $this->getOrderDetailsByReference($shipmentReference);

        if ($details === null) {
            throw new OrderNotFound("Order details for reference [$shipmentReference] not found.");
        }

        $details->setShipmentLabels($labels);

        $this->getOrderDetailsRepository()->update($details);
    }

    /**
     * Sets order packlink shipment tracking history to an order for given shipment.
     *
     * @param Shipment $shipment Packlink shipment details.
     * @param Tracking[] $trackingHistory Shipment tracking history.
     *
     * @throws \Doctrine\ORM\OptimisticLockException
     * @throws \Logeecom\Infrastructure\ORM\Exceptions\QueryFilterInvalidParamException
     * @throws \Logeecom\Infrastructure\ORM\Exceptions\RepositoryNotRegisteredException
     * @throws \Packlink\BusinessLogic\Order\Exceptions\OrderNotFound When order with provided reference is not found.
     */
    public function updateTrackingInfo(Shipment $shipment, array $trackingHistory)
    {
        $orderDetails = $this->getOrderDetailsByReference($shipment->reference);

        if ($orderDetails === null) {
            throw new OrderNotFound("Order details for reference [$shipment->reference] not found.");
        }

        /** @var \Shopware\Models\Order\Order $sourceOrder */
        $sourceOrder = $this->getShopwareOrderRepository()->find($orderDetails->getOrderId());
        if ($sourceOrder === null) {
            throw new OrderNotFound("Source order with id [{$orderDetails->getOrderId()}] not found.");
        }

        $orderDetails->setShippingCost($shipment->price);
        $orderDetails->setCarrierTrackingUrl($shipment->carrierTrackingUrl);
        $orderDetails->setCarrierTrackingNumbers($shipment->trackingCodes);

        if (!empty($trackingHistory)) {
            $history = $this->sortTrackingRecords($trackingHistory);
            $latestTrackingRecord = $history[0];
            $orderDetails->setShippingStatus($latestTrackingRecord->description, $latestTrackingRecord->timestamp);
        }

        if (isset($shipment->trackingCodes[0])) {
            $sourceOrder->setTrackingCode($shipment->trackingCodes[0]);
            Shopware()->Models()->flush($sourceOrder);
        }
    }

    /**
     * Sets order packlink shipping status to an order by shipment reference.
     *
     * @param string $shipmentReference Packlink shipment reference.
     * @param string $shippingStatus Packlink shipping status.
     *
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     * @throws \Doctrine\ORM\TransactionRequiredException
     * @throws \Logeecom\Infrastructure\ORM\Exceptions\QueryFilterInvalidParamException
     * @throws \Logeecom\Infrastructure\ORM\Exceptions\RepositoryNotRegisteredException
     * @throws \Packlink\BusinessLogic\Order\Exceptions\OrderNotFound When order with provided reference is not found.
     */
    public function setShippingStatusByReference($shipmentReference, $shippingStatus)
    {
        $orderDetails = $this->getOrderDetailsByReference($shipmentReference);

        if ($orderDetails === null) {
            throw new OrderNotFound("Order details for reference [$shipmentReference] not found.");
        }

        /** @var \Shopware\Models\Order\Order $sourceOrder */
        $sourceOrder = $this->getShopwareOrderRepository()->find($orderDetails->getOrderId());
        if ($sourceOrder === null) {
            throw new OrderNotFound("Source order with id [{$orderDetails->getOrderId()}] not found.");
        }

        $orderDetails->setStatus($shippingStatus);
        $this->getOrderDetailsRepository()->update($orderDetails);

        $statusMap = $this->getConfigService()->getOrderStatusMappings();

        if (isset($statusMap[$shippingStatus]) && $statusMap[$shippingStatus] !== '') {
            $status = Shopware()->Models()->find('Shopware\Models\Order\Status', $statusMap[$shippingStatus]);

            if ($status) {
                $sourceOrder->setOrderStatus($status);
                Shopware()->Models()->flush($sourceOrder);
            }
        }
    }

    /**
     * Sets shipping price to an order by shipment reference.
     *
     * @param string $shipmentReference Packlink shipment reference.
     * @param float $price Shipment price.
     *
     * @throws \Logeecom\Infrastructure\ORM\Exceptions\QueryFilterInvalidParamException
     * @throws \Logeecom\Infrastructure\ORM\Exceptions\RepositoryNotRegisteredException
     * @throws \Packlink\BusinessLogic\Order\Exceptions\OrderNotFound When order with provided reference is not found.
     */
    public function setShippingPriceByReference($shipmentReference, $price)
    {
        $details = $this->getOrderDetailsByReference($shipmentReference);

        if ($details === null) {
            throw new OrderNotFound("Order details for reference [$shipmentReference] not found.");
        }

        $details->setShippingCost($price);

        $this->getOrderDetailsRepository()->update($details);
    }

    /**
     * Marks shipment identified by provided reference as deleted on Packlink.
     *
     * @param string $shipmentReference Packlink shipment reference.
     *
     * @throws \Logeecom\Infrastructure\ORM\Exceptions\QueryFilterInvalidParamException
     * @throws \Logeecom\Infrastructure\ORM\Exceptions\RepositoryNotRegisteredException
     * @throws \Packlink\BusinessLogic\Order\Exceptions\OrderNotFound When order with provided reference is not found.
     */
    public function markShipmentDeleted($shipmentReference)
    {
        $details = $this->getOrderDetailsByReference($shipmentReference);

        if ($details === null) {
            throw new OrderNotFound("Order details for reference [$shipmentReference] not found.");
        }

        $details->setDeleted(true);

        $this->getOrderDetailsRepository()->update($details);
    }

    /**
     * Returns whether shipment identified by provided reference is deleted on Packlink or not.
     *
     * @param string $shipmentReference Packlink shipment reference.
     *
     * @return bool Returns TRUE if shipment has been deleted; otherwise returns FALSE.
     *
     * @throws \Logeecom\Infrastructure\ORM\Exceptions\QueryFilterInvalidParamException
     * @throws \Logeecom\Infrastructure\ORM\Exceptions\RepositoryNotRegisteredException
     * @throws \Packlink\BusinessLogic\Order\Exceptions\OrderNotFound When order with provided reference is not found.
     */
    public function isShipmentDeleted($shipmentReference)
    {
        $details = $this->getOrderDetailsByReference($shipmentReference);

        if ($details === null) {
            throw new OrderNotFound("Order details for reference [$shipmentReference] not found.");
        }

        return $details->isDeleted();
    }

    /**
     * Returns whether shipment identified by provided reference has Packlink shipment label set.
     *
     * @param string $shipmentReference Packlink shipment reference.
     *
     * @return bool Returns TRUE if label is set; otherwise, FALSE.
     *
     * @throws \Logeecom\Infrastructure\ORM\Exceptions\QueryFilterInvalidParamException
     * @throws \Logeecom\Infrastructure\ORM\Exceptions\RepositoryNotRegisteredException
     */
    public function isLabelSet($shipmentReference)
    {
        $details = $this->getOrderDetailsByReference($shipmentReference);

        return $details !== null && !empty($details->getShipmentLabels());
    }

    /**
     * Maps Shopware address to Packlink address.
     *
     * @param \Shopware\Models\Order\Order $sourceOrder
     *
     * @return \Packlink\BusinessLogic\Order\Objects\Address
     */
    protected function getOrderAddress(\Shopware\Models\Order\Order $sourceOrder)
    {
        $address = new Address();

        $shippingAddress = $sourceOrder->getShipping();
        $address->setName($shippingAddress->getFirstName());
        $address->setSurname($shippingAddress->getLastName());
        $address->setEmail($sourceOrder->getCustomer()->getEmail());
        $address->setCompany($shippingAddress->getCompany());
        $street1 = $shippingAddress->getStreet() . $shippingAddress->getAdditionalAddressLine1();
        $address->setStreet1($street1);
        $address->setStreet2($shippingAddress->getAdditionalAddressLine2());
        $address->setZipCode($shippingAddress->getZipCode());
        $address->setCity($shippingAddress->getCity());
        if (method_exists($shippingAddress, 'getPhone')) {
            $address->setPhone($shippingAddress->getPhone());
        }
        $address->setCountry($shippingAddress->getCountry()->getIso());

        return $address;
    }

    /**
     * Retrieves order items from source order.
     *
     * @param \Shopware\Models\Order\Order $sourceOrder
     *
     * @return Item[]
     */
    protected function getOrderItems(\Shopware\Models\Order\Order $sourceOrder)
    {
        $result = [];

        $defaultParcel = $this->getConfigService()->getDefaultParcel();
        if ($defaultParcel === null) {
            $defaultParcel = ParcelInfo::defaultParcel();
        }

        /** @var \Shopware\Models\Order\Detail $detail */
        foreach ($sourceOrder->getDetails() as $detail) {
            if (!$detail->getArticleId()) {
                /** Item should not be shipped. */
                continue;
            }

            $orderItem = new Item();

            $totalPrice = $detail->getPrice() / (1 - $detail->getTaxRate() / 100);
            $orderItem->setTotalPrice($totalPrice);
            $orderItem->setPrice($detail->getPrice());
            $orderItem->setQuantity($detail->getQuantity());
            $orderItem->setPictureUrl($this->getImageSource($detail->getArticleId()));

            /** @var Article $article */
            $article = $this->getShopwareArticleRepository()->find($detail->getArticleId());

            $orderItem->setCategoryName($this->getCategoryName($article));

            $productDetails = $article->getMainDetail();
            $weight = $productDetails->getWeight();
            /** @noinspection TypeUnsafeComparisonInspection */
            $orderItem->setWeight(round($weight != 0 ? $weight : $defaultParcel->weight, 2));
            $orderItem->setHeight(round($productDetails->getHeight() ?: $defaultParcel->height, 2));
            $orderItem->setLength(round($productDetails->getLen() ?: $defaultParcel->length, 2));
            $orderItem->setWidth(round($productDetails->getWidth() ?: $defaultParcel->width, 2));

            $result[] = $orderItem;
        }

        return $result;
    }

    /**
     * Retrieves order details by reference.
     *
     * @param $reference
     *
     * @return OrderShipmentDetails | null
     *
     * @throws \Logeecom\Infrastructure\ORM\Exceptions\QueryFilterInvalidParamException
     * @throws \Logeecom\Infrastructure\ORM\Exceptions\RepositoryNotRegisteredException
     */
    protected function getOrderDetailsByReference($reference)
    {
        $filter = new QueryFilter();
        $filter->where('reference', Operators::EQUALS, $reference);

        /** @var OrderShipmentDetails $entity */
        $entity = $this->getOrderDetailsRepository()->selectOne($filter);

        return $entity;
    }

    /**
     * Retrieves order details by order id.
     *
     * @param $orderId
     *
     * @return \Packlink\BusinessLogic\Order\Models\OrderShipmentDetails
     * @throws \Logeecom\Infrastructure\ORM\Exceptions\QueryFilterInvalidParamException
     * @throws \Logeecom\Infrastructure\ORM\Exceptions\RepositoryNotRegisteredException
     */
    protected function getOrderDetailsById($orderId)
    {
        $filter = new QueryFilter();
        $filter->where('orderId', Operators::EQUALS, $orderId);

        /** @var OrderShipmentDetails $entity */
        $entity = $this->getOrderDetailsRepository()->selectOne($filter);

        return $entity;
    }

    /**
     * Retrieves order details repository.
     *
     * @return \Logeecom\Infrastructure\ORM\Interfaces\RepositoryInterface
     *
     * @throws \Logeecom\Infrastructure\ORM\Exceptions\RepositoryNotRegisteredException
     */
    protected function getOrderDetailsRepository()
    {
        if ($this->orderDetailsRepository === null) {
            $this->orderDetailsRepository = RepositoryRegistry::getRepository(OrderShipmentDetails::getClassName());
        }

        return $this->orderDetailsRepository;
    }

    /**
     * Retrieves category name.
     *
     * @param \Shopware\Models\Article\Article | null $article
     *
     * @return string
     */
    private function getCategoryName(Article $article = null)
    {
        if (!$article) {
            return '';
        }

        /** @var \Shopware\Models\Category\Category $category */
        $category = $article->getCategories()->first();

        return $category !== null ? $category->getPath() : '';
    }

    /**
     * Retrieves Shopware order repository.
     *
     * @return \Shopware\Models\Order\Repository
     */
    protected function getShopwareOrderRepository()
    {
        return Shopware()->Models()->getRepository(\Shopware\Models\Order\Order::class);
    }

    /**
     * Retrieves article repository.
     *
     * @return \Shopware\Models\Article\Repository
     */
    protected function getShopwareArticleRepository()
    {
        return Shopware()->Models()->getRepository(Article::class);
    }

    /**
     * Retrieves img source.
     *
     * @param $articleId
     *
     * @return string
     */
    protected function getImageSource($articleId)
    {
        $product = Shopware()->Modules()->Articles()->sGetArticleById($articleId);

        if (empty($product)) {
            return '';
        }

        $imgData = Shopware()->Modules()->Articles()->sGetConfiguratorImage($product);

        return !empty($imgData['image']['source']) ? $imgData['image']['source'] : '';
    }

    /**
     * Retrieves config service.
     *
     * @return \Packlink\BusinessLogic\Configuration
     */
    protected function getConfigService()
    {
        if ($this->configService === null) {
            $this->configService = ServiceRegister::getService(Configuration::CLASS_NAME);
        }

        return $this->configService;
    }

    /**
     * Sort tracking history records by timestamps in descending order.
     *
     * @param Tracking[] $trackingRecords Array of tracking history records.
     *
     * @return array Sorted array of tracking history records.
     */
    protected function sortTrackingRecords(array $trackingRecords)
    {
        usort(
            $trackingRecords,
            function ($first, $second) {
                if ($first->timestamp === $second->timestamp) {
                    return 0;
                }

                return ($first->timestamp < $second->timestamp) ? 1 : -1;
            }
        );

        return $trackingRecords;
    }

    /**
     * Sets dropoff id if dropoff has been used for order.
     *
     * @param \Packlink\BusinessLogic\Order\Objects\Order $order
     * @param int $orderId
     *
     * @throws \Logeecom\Infrastructure\ORM\Exceptions\QueryFilterInvalidParamException
     * @throws \Logeecom\Infrastructure\ORM\Exceptions\RepositoryNotRegisteredException
     */
    protected function setDropoffId(Order $order, $orderId)
    {
        $filter = new QueryFilter();
        $filter->where('orderId', Operators::EQUALS, $orderId);
        /** @var OrderDropoffMap $map | null */
        $map = $this->getOrderDropoffRepository()->selectOne($filter);
        if ($map !== null) {
            $order->setShippingDropOffId((string)$map->dropoff['id']);
        }
    }

    /**
     * Retrieves order dropoff map repository.
     *
     * @return \Logeecom\Infrastructure\ORM\Interfaces\RepositoryInterface
     *
     * @throws \Logeecom\Infrastructure\ORM\Exceptions\RepositoryNotRegisteredException
     */
    protected function getOrderDropoffRepository()
    {
        if ($this->orderDropoffRepository === null) {
            $this->orderDropoffRepository = RepositoryRegistry::getRepository(OrderDropoffMap::getClassName());
        }

        return $this->orderDropoffRepository;
    }

    /**
     * Sets shipping method id if shipping method is packlink carrier.
     *
     * @param \Packlink\BusinessLogic\Order\Objects\Order $order
     * @param int $carrierId
     *
     * @throws \Logeecom\Infrastructure\ORM\Exceptions\QueryFilterInvalidParamException
     * @throws \Logeecom\Infrastructure\ORM\Exceptions\RepositoryNotRegisteredException
     */
    protected function setShippingMethodId(Order $order, $carrierId)
    {
        $filter = new QueryFilter();
        $filter->where('shopwareCarrierId', Operators::EQUALS, $carrierId);

        /** @var ShippingMethodMap $map | null */
        $map = $this->getShippingMethodMapRepository()->selectOne($filter);

        if ($map) {
            $order->setShippingMethodId($map->shippingMethodId);
        }
    }

    /**
     * Retrieves shipping method map repository.
     *
     * @return \Logeecom\Infrastructure\ORM\Interfaces\RepositoryInterface
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