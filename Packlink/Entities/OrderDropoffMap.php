<?php

namespace Packlink\Entities;

use Packlink\Infrastructure\ORM\Configuration\EntityConfiguration;
use Packlink\Infrastructure\ORM\Configuration\IndexMap;
use Packlink\Infrastructure\ORM\Entity;

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
     * Creates instance of this class.
     *
     * @param array $data
     *
     * @return static
     *
     * @noinspection PhpDocSignatureInspection
     */
    public static function create(array $data)
    {
        return new self();
    }

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