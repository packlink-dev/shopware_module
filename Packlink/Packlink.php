<?php
namespace Packlink;

require_once __DIR__ . '/vendor/autoload.php';

use Doctrine\ORM\EntityManager;
use Logeecom\Infrastructure\Logger\Logger;
use Logeecom\Infrastructure\ServiceRegister;
use Packlink\Bootstrap\Bootstrap;
use Packlink\Bootstrap\Database;
use Packlink\BusinessLogic\Configuration;
use Shopware\Components\Plugin;
use Shopware\Components\Plugin\Context\InstallContext;
use Shopware\Components\Plugin\Context\UninstallContext;
use Symfony\Component\DependencyInjection\ContainerBuilder;

require_once __DIR__ . '/CSRFWhitelistAware.php';

class Packlink extends Plugin
{
    const INITIAL_VERSION = '0.0.1';
    /**
     * @var Configuration
     */
    protected $configService;

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
        Bootstrap::init();

        if ($context->getCurrentVersion() === self::INITIAL_VERSION) {
            /** @var EntityManager $entityManager */
            $entityManager = $this->container->get('models');
            $db = new Database($entityManager);
            $db->install();

            Logger::logInfo("Database for version [{$context->getCurrentVersion()}] created...", 'Integration');
        }

        Logger::logInfo('Installation completed...', 'Integration');
    }

    /**
     * Performs plugin uninstall.
     *
     * @param \Shopware\Components\Plugin\Context\UninstallContext $context
     */
    public function uninstall(UninstallContext $context)
    {
        Bootstrap::init();;

        if (!$context->keepUserData()) {
            /** @var EntityManager $entityManager */
            $entityManager = $this->container->get('models');
            $db = new Database($entityManager);
            $db->uninstall();
        }
    }

    /**
     * Retrieves configuration service;
     *
     * @return \Packlink\BusinessLogic\Configuration
     */
    protected function getConfigService()
    {
        if ($this->configService === null) {
            $this->configService = ServiceRegister::getService(Configuration::CLASS_NAME);
        }

        return $this->configService;
    }
}