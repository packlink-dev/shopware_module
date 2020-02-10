<?php

namespace Packlink\Services\BusinessLogic;

use Logeecom\Infrastructure\ORM\QueryFilter\Operators;
use Logeecom\Infrastructure\ORM\QueryFilter\QueryFilter;
use Logeecom\Infrastructure\ORM\RepositoryRegistry;
use Logeecom\Infrastructure\ServiceRegister;
use Packlink\BusinessLogic\Configuration;
use Packlink\BusinessLogic\Http\DTO\ParcelInfo;
use Packlink\BusinessLogic\Order\Exceptions\OrderNotFound;
use Packlink\BusinessLogic\Order\Interfaces\ShopOrderService as BaseShopOrderService;
use Packlink\BusinessLogic\Order\Objects\Address;
use Packlink\BusinessLogic\Order\Objects\Item;
use Packlink\BusinessLogic\Order\Objects\Order;
use Packlink\Entities\OrderDropoffMap;
use Packlink\Entities\ShippingMethodMap;
use Shopware\Models\Article\Article;

class ShopOrderService implements BaseShopOrderService
{
    /**
     * @var \Logeecom\Infrastructure\ORM\Interfaces\RepositoryInterface
     */
    protected $orderDetailsRepository;
    /**
     * @var \Logeecom\Infrastructure\ORM\Interfaces\RepositoryInterface
     */
    protected $orderDropoffRepository;
    /**
     * @var \Logeecom\Infrastructure\ORM\Interfaces\RepositoryInterface
     */
    protected $shippingMethodMapRepository;
    /**
     * @var \Packlink\BusinessLogic\Configuration
     */
    protected $configService;

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
     * @inheritDoc
     */
    public function updateTrackingInfo($orderId, array $trackings)
    {
        /** @var \Shopware\Models\Order\Order $sourceOrder */
        $sourceOrder = $this->getShopwareOrderRepository()->find($orderId);
        if ($sourceOrder === null) {
            throw new OrderNotFound("Source order with id [{$orderId}] not found.");
        }

        if (isset($trackings[0])) {
            $sourceOrder->setTrackingCode($trackings[0]);
            Shopware()->Models()->flush($sourceOrder);
        }
    }

    /**
     * @inheritDoc
     * @noinspection PhpUnhandledExceptionInspection
     */
    public function updateShipmentStatus($orderId, $shippingStatus)
    {
        /** @var \Shopware\Models\Order\Order $sourceOrder */
        $sourceOrder = $this->getShopwareOrderRepository()->find($orderId);
        if ($sourceOrder === null) {
            throw new OrderNotFound("Source order with id [{$orderId}] not found.");
        }

        $statusMap = $this->getConfigService()->getOrderStatusMappings();

        if (isset($statusMap[$shippingStatus]) && $statusMap[$shippingStatus] !== '') {
            /** @var \Shopware\Models\Order\Status $status */
            $status = Shopware()->Models()->find('Shopware\Models\Order\Status', $statusMap[$shippingStatus]);

            if ($status) {
                $sourceOrder->setOrderStatus($status);
                Shopware()->Models()->flush($sourceOrder);
            }
        }
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
        $billingAddress = $sourceOrder->getBilling();
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
        } else if (method_exists($billingAddress, 'getPhone')) {
            $address->setPhone($billingAddress->getPhone());
        }

        if (!$address->getPhone() && ($phone = $this->getConfigService()->getDefaultWarehouse()->phone)) {
            $address->setPhone($phone);
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
    private function getShippingMethodMapRepository()
    {
        if ($this->shippingMethodMapRepository === null) {
            $this->shippingMethodMapRepository = RepositoryRegistry::getRepository(ShippingMethodMap::getClassName());
        }

        return $this->shippingMethodMapRepository;
    }

    /**
     * Retrieves order dropoff map repository.
     *
     * @return \Logeecom\Infrastructure\ORM\Interfaces\RepositoryInterface
     *
     * @throws \Logeecom\Infrastructure\ORM\Exceptions\RepositoryNotRegisteredException
     */
    private function getOrderDropoffRepository()
    {
        if ($this->orderDropoffRepository === null) {
            $this->orderDropoffRepository = RepositoryRegistry::getRepository(OrderDropoffMap::getClassName());
        }

        return $this->orderDropoffRepository;
    }

    /**
     * Retrieves Shopware order repository.
     *
     * @return \Shopware\Models\Order\Repository
     * @noinspection PhpIncompatibleReturnTypeInspection
     */
    private function getShopwareOrderRepository()
    {
        return Shopware()->Models()->getRepository(\Shopware\Models\Order\Order::class);
    }

    /**
     * Retrieves article repository.
     *
     * @return \Shopware\Models\Article\Repository
     * @noinspection PhpIncompatibleReturnTypeInspection
     */
    private function getShopwareArticleRepository()
    {
        return Shopware()->Models()->getRepository(Article::class);
    }

    /**
     * Retrieves config service.
     *
     * @return \Packlink\BusinessLogic\Configuration
     */
    private function getConfigService()
    {
        if ($this->configService === null) {
            $this->configService = ServiceRegister::getService(Configuration::CLASS_NAME);
        }

        return $this->configService;
    }
}
