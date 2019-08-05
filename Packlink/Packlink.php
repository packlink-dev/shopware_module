<?php
namespace Packlink;

use Shopware\Components\Plugin;
use Symfony\Component\DependencyInjection\ContainerBuilder;

require_once __DIR__ . '/CSRFWhitelistAware.php';

class Packlink extends Plugin
{
    /**
     * @inheritDoc
     */
    public function build(ContainerBuilder $container)
    {
        $container->setParameter('packlink.plugin_dir', $this->getPath());
        parent::build($container);
    }
}