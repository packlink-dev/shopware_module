<?php

namespace Packlink\Entities;

use Logeecom\Infrastructure\ORM\Configuration\EntityConfiguration;
use Logeecom\Infrastructure\ORM\Configuration\IndexMap;
use Logeecom\Infrastructure\ORM\Entity;

class OrderDropoffMap extends Entity
{
    const CLASS_NAME = __CLASS__;

    const TYPE = 'OrderDropoffMap';

    /** @var int */
    public $orderId;
    /** @var array */
    public $dropoff;

    protected $fields = ['id', 'orderId', 'dropoff'];

    /**
     * Returns entity configuration object.
     *
     * @return EntityConfiguration Configuration object.
     */
    public function getConfig()
    {
        $map = new IndexMap();
        $map->addIntegerIndex('orderId');

        return new EntityConfiguration($map, self::TYPE);
    }
}