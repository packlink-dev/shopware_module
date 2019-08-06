<?php
namespace Packlink;

use Doctrine\ORM\EntityManager;
use Packlink\Bootstrap\Database;
use Shopware\Components\Plugin;
use Shopware\Components\Plugin\Context\InstallContext;
use Shopware\Components\Plugin\Context\UninstallContext;
use Symfony\Component\DependencyInjection\ContainerBuilder;

require_once __DIR__ . '/CSRFWhitelistAware.php';

class Packlink extends Plugin
{
    const INITIAL_VERSION = '1.0.0';

    /**
     * @inheritDoc
     */
    public function build(ContainerBuilder $container)
    {
        $container->setParameter('packlink.plugin_dir', $this->getPath());
        parent::build($container);
    }

    /**
     * Performs plugin installation.
     *
     * @param InstallContext $context
     */
    public function install(InstallContext $context)
    {
        if ($context->getCurrentVersion() === self::INITIAL_VERSION) {
            /** @var EntityManager $entityManager */
            $entityManager = $this->container->get('models');
            $db = new Database($entityManager);
            $db->install();
        }
    }

    public function uninstall(UninstallContext $context)
    {
        if (!$context->keepUserData()) {
            /** @var EntityManager $entityManager */
            $entityManager = $this->container->get('models');
            $db = new Database($entityManager);
            $db->uninstall();
        }
    }
}