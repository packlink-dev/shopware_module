<?php

namespace Packlink\Tests\TestComponents;

use Doctrine\ORM\EntityManager;
use Logeecom\Infrastructure\BootstrapComponent;
use Logeecom\Tests\Infrastructure\ORM\AbstractGenericQueueItemRepositoryTest;
use Packlink\Tests\TestComponents\Components\TestDatabase;
use Packlink\Tests\TestComponents\Components\TestQueueItemRepository;

class BaseQueueItemRepositoryTestAdapter extends AbstractGenericQueueItemRepositoryTest
{
    /**
     * @var \Doctrine\ORM\EntityManager
     */
    protected $entityManager;

    /**
     * Sets entity manager.
     *
     * @param \Doctrine\ORM\EntityManager $entityManager
     */
    public function setEntityManager(EntityManager $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    /**
     * @inheritDoc
     * @throws \Logeecom\Infrastructure\TaskExecution\Exceptions\TaskRunnerStatusStorageUnavailableException
     */
    public function setUp()
    {
        $database = new TestDatabase($this->entityManager);
        $database->install();

        BootstrapComponent::init();

        parent::setUp();
    }

    /**
     * @inheritDoc
     */
    public function tearDown()
    {
        parent::tearDown();
    }

    /**
     * @return string
     */
    public function getQueueItemEntityRepositoryClass()
    {
        return TestQueueItemRepository::getClassName();
    }

    /**
     * Cleans up all storage services used by repositories
     * @throws \Logeecom\Infrastructure\ORM\Exceptions\RepositoryNotRegisteredException
     */
    public function cleanUpStorage()
    {
        $database = new TestDatabase($this->entityManager);
        $database->uninstall();
        $this->entityManager->clear();
    }
}
