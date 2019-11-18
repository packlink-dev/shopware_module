<?php

namespace Packlink\Models;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\Index;

/**
 * @ORM\Entity
 * @ORM\Table(
 *     name="packlink_entity",
 *     indexes={
 *              @Index(name="type", columns={"type"})
 *          }
 *      )
 */
class PacklinkEntity extends BaseEntity
{
}