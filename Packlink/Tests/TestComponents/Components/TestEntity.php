<?php

namespace Packlink\Tests\TestComponents\Components;

use Doctrine\ORM\Mapping\Index;
use Doctrine\ORM\Mapping as ORM;
use Packlink\Models\BaseEntity;

/**
 * @ORM\Entity
 * @ORM\Table(
 *     name="test_packlink_entity",
 *     indexes={
 *              @Index(name="type", columns={"type"})
 *          }
 *      )
 */
class TestEntity extends BaseEntity
{
}