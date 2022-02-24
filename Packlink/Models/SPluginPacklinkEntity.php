<?php

namespace Packlink\Models;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\Index;

/**
 * @ORM\Entity
 * @ORM\Table(
 *     name="s_plugin_packlink_entity",
 *     indexes={
 *              @Index(name="type", columns={"type"})
 *          }
 *      )
 */
class SPluginPacklinkEntity extends BaseEntity
{
}