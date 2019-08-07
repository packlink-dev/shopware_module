<?php

namespace Packlink\Tests\TestComponents;

use Doctrine\ORM\EntityManager;
use Logeecom\Tests\Infrastructure\ORM\AbstractGenericStudentRepositoryTest;
use Packlink\Tests\TestComponents\Components\TestBaseRepository;
use Packlink\Tests\TestComponents\Components\TestDatabase;

class BaseRepositoryTestAddatper extends AbstractGenericStudentRepositoryTest
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
     */
    public function setUp()
    {
        $database = new TestDatabase($this->entityManager);
        $database->install();

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
     * @inheritDoc
     */
    public function getStudentEntityRepositoryClass()
    {
        return TestBaseRepository::class;
    }

    /**
     * @inheritDoc
     */
    public function cleanUpStorage()
    {
        $database = new TestDatabase($this->entityManager);
        $database->uninstall();
        $this->entityManager->clear();
    }
}