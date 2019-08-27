<?php

namespace Packlink\Entities;

use Logeecom\Infrastructure\ORM\Configuration\EntityConfiguration;
use Logeecom\Infrastructure\ORM\Configuration\IndexMap;
use Logeecom\Infrastructure\ORM\Entity;

class ShippingMethodMap extends Entity
{
    /**
     * Fully qualified name of this class.
     */
    const CLASS_NAME = __CLASS__;

    /** @var int */
    public $shopwareCarrierId;
    /** @var int */
    public $shippingMethodId;
    /** @var boolean */
    public $isDropoff;

    /** @var array  */
    protected $fields = ['id', 'shopwareCarrierId', 'shippingMethodId', 'isDropoff'];

    /**
     * Returns full class name.
     *
     * @return string Fully qualified class name.
     */
    public static function getClassName()
    {
        return static::CLASS_NAME;
    }

    /**
     * Returns entity configuration object.
     *
     * @return \Logeecom\Infrastructure\ORM\Configuration\EntityConfiguration Configuration object.
     */
    public function getConfig()
    {
        $map = new IndexMap();
        $map->addIntegerIndex('shopwareCarrierId')
            ->addIntegerIndex('shippingMethodId')
            ->addBooleanIndex('isDropoff');

        return new EntityConfiguration($map, 'ShippingMethodMap');
    }
}