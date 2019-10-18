<?php

namespace Packlink;

require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/CSRFWhitelistAware.php';

use Doctrine\ORM\EntityManager;
use Logeecom\Infrastructure\Logger\Logger;
use Packlink\Bootstrap\Bootstrap;
use Packlink\Bootstrap\Database;
use Packlink\Utilities\ObsoleteFilesRemover;
use Packlink\Utilities\UpdateScriptsRunner;
use Packlink\Utilities\VersionedFileReader;
use Shopware\Components\Plugin;
use Shopware\Components\Plugin\Context\ActivateContext;
use Shopware\Components\Plugin\Context\DeactivateContext;
use Shopware\Components\Plugin\Context\InstallContext;
use Shopware\Components\Plugin\Context\UninstallContext;
use Shopware\Components\Plugin\Context\UpdateContext;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class Packlink extends Plugin
{
    /**
     * @var \Packlink\Services\BusinessLogic\ConfigurationService
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
     *
     * @throws \Logeecom\Infrastructure\TaskExecution\Exceptions\TaskRunnerStatusStorageUnavailableException
     * @throws \Exception
     */
    public function install(InstallContext $context)
    {
        Bootstrap::init();

        Shopware()->Container()->get('shopware.snippet_database_handler')->loadToDatabase(
            $this->getPath() . '/Resources/snippets/'
        );

        /** @var EntityManager $entityManager */
        $entityManager = $this->container->get('models');
        $db = new Database($entityManager);
        $db->install();
        Logger::logInfo("Database for version [{$context->getCurrentVersion()}] created...", 'Integration');
        Logger::logInfo('Installation completed...', 'Integration');
    }

    /**
     * Performs plugin uninstall.
     *
     * @param \Shopware\Components\Plugin\Context\UninstallContext $context
     *
     * @throws \Logeecom\Infrastructure\ORM\Exceptions\RepositoryNotRegisteredException
     */
    public function uninstall(UninstallContext $context)
    {
        Bootstrap::init();

        if (!$context->keepUserData()) {
            /** @var EntityManager $entityManager */
            $entityManager = $this->container->get('models');
            $db = new Database($entityManager);
            $db->uninstall();
        }

        $context->scheduleClearCache(InstallContext::CACHE_LIST_DEFAULT);
    }

    /**
     * Activates plugin.
     *
     * @param \Shopware\Components\Plugin\Context\ActivateContext $context
     *
     * @throws \Logeecom\Infrastructure\ORM\Exceptions\RepositoryNotRegisteredException
     */
    public function activate(ActivateContext $context)
    {
        Bootstrap::init();

        parent::activate($context);

        /** @var EntityManager $entityManager */
        $entityManager = $this->container->get('models');
        $db = new Database($entityManager);
        $db->activate();
    }

    /**
     * Deactivates plugin.
     *
     * @param \Shopware\Components\Plugin\Context\DeactivateContext $context
     *
     * @throws \Logeecom\Infrastructure\ORM\Exceptions\RepositoryNotRegisteredException
     */
    public function deactivate(DeactivateContext $context)
    {
        Bootstrap::init();

        parent::deactivate($context);

        /** @var EntityManager $entityManager */
        $entityManager = $this->container->get('models');
        $db = new Database($entityManager);
        $db->deactivate();
    }

    /**
     * Performs plugin update.
     *
     * @param \Shopware\Components\Plugin\Context\UpdateContext $context
     *
     * @return boolean
     */
    public function update(UpdateContext $context)
    {
        Bootstrap::init();

        parent::update($context);

        $result = $this->removeObsoleteFiles($context);
        $result |= $this->updateDatabase($context);
        $result |= $this->executeScripts($context);

        if (!$result) {
            Logger::logError(
                "Failed to update plugin from {$context->getCurrentVersion()} to {$context->getUpdateVersion()}."
            );
        }

        return $result;
    }

    /**
     * Removes obsolete files.
     *
     * @param \Shopware\Components\Plugin\Context\UpdateContext $context
     *
     * @return bool
     */
    protected function removeObsoleteFiles(UpdateContext $context)
    {
        $version = $context->getCurrentVersion();
        $fileReader = new VersionedFileReader($this->getMigrationDirectory('ObsoleteFiles'), $version);
        $obsoleteFilesRemover = new ObsoleteFilesRemover($this->getPath(), $fileReader);

        return $obsoleteFilesRemover->remove();
    }

    /**
     * Updates database.
     *
     * @param \Shopware\Components\Plugin\Context\UpdateContext $context
     *
     * @return bool
     */
    protected function updateDatabase(UpdateContext $context)
    {
        $version = $context->getCurrentVersion();
        /** @var EntityManager $entityManager */
        $entityManager = $this->container->get('models');
        $db = new Database($entityManager);

        $fileReader = new VersionedFileReader($this->getMigrationDirectory('Database'), $version);

        return $db->update($fileReader);
    }

    /**
     * Executes update scripts.
     *
     * @param \Shopware\Components\Plugin\Context\UpdateContext $context
     *
     * @return bool
     */
    protected function executeScripts(UpdateContext $context)
    {
        $version = $context->getCurrentVersion();
        $fileReader = new VersionedFileReader($this->getMigrationDirectory('Scripts'), $version);
        $updateScriptRunner = new UpdateScriptsRunner($fileReader);

        return $updateScriptRunner->run();
    }

    /**
     * Retrieves migrations subdirectory.
     *
     * @param string $directory
     *
     * @return string
     */
    protected function getMigrationDirectory($directory)
    {
        return $this->getPath() . '/Migrations/' . $directory . '/';
    }
}