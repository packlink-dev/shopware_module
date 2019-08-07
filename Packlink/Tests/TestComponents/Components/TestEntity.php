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
 *              @Index(name="index_1", columns={"index_1"}),
 *              @Index(name="index_2", columns={"index_2"}),
 *              @Index(name="index_3", columns={"index_3"}),
 *              @Index(name="index_4", columns={"index_4"}),
 *              @Index(name="index_5", columns={"index_5"}),
 *              @Index(name="index_6", columns={"index_6"}),
 *              @Index(name="index_7", columns={"index_7"})
 *          }
 *      )
 */
class TestEntity extends BaseEntity
{
}