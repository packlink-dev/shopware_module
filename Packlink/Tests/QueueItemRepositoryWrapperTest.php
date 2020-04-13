<?php

namespace Packlink\Tests;

require_once __DIR__ . '/../vendor/autoload.php';

use Packlink\Tests\TestComponents\BaseQueueItemRepositoryTestAdapter;
use PHPUnit\Framework\TestCase;

class QueueItemRepositoryWrapperTest extends TestCase
{
    protected static $ensureLoadedPlugins = [
        'Packlink' => []
    ];
    /**
     * @var BaseQueueItemRepositoryTestAdapter
     */
    protected $baseTest;

    /**
     * QueueItemRepositoryWrapperTest constructor.
     *
     * @throws \Exception
     */
    public function __construct()
    {
        parent::__construct(...func_get_args());
        $this->baseTest = new BaseQueueItemRepositoryTestAdapter(...func_get_args());
        $entityManager = Shopware()->Container()->get('models');
        $this->baseTest->setEntityManager($entityManager);
    }

    /**
     * Proxies method to base test.
     *
     * @param $name
     * @param $arguments
     */
    public function __call($name, $arguments)
    {
        if (is_callable([$this->baseTest, $name])) {
            $this->baseTest->$name(...$arguments);
        }
    }

    /**
     * @throws \Logeecom\Infrastructure\ORM\Exceptions\RepositoryClassException
     * @throws \Logeecom\Infrastructure\ORM\Exceptions\RepositoryNotRegisteredException
     */
    public function testRegisteredRepositories()
    {
        $this->baseTest->testRegisteredRepositories();
    }

    /**
     * @depends testRegisteredRepositories
     *
     * @throws \Logeecom\Infrastructure\ORM\Exceptions\RepositoryClassException
     * @throws \Logeecom\Infrastructure\ORM\Exceptions\RepositoryNotRegisteredException
     */
    public function testQueueItemMassInsert()
    {
        $this->baseTest->testQueueItemMassInsert();
    }

    /**
     * @throws \Logeecom\Infrastructure\ORM\Exceptions\QueryFilterInvalidParamException
     * @throws \Logeecom\Infrastructure\ORM\Exceptions\RepositoryClassException
     * @throws \Logeecom\Infrastructure\ORM\Exceptions\RepositoryNotRegisteredException
     */
    public function testUpdate()
    {
        $this->baseTest->testQueueItemMassInsert();

        $this->baseTest->testUpdate();
    }

    /**
     * @throws \Logeecom\Infrastructure\ORM\Exceptions\RepositoryClassException
     * @throws \Logeecom\Infrastructure\ORM\Exceptions\RepositoryNotRegisteredException
     */
    public function testQueryAllQueueItems()
    {
        $this->baseTest->testQueueItemMassInsert();

        $this->baseTest->testQueryAllQueueItems();
    }

    /**
     * @throws \Logeecom\Infrastructure\ORM\Exceptions\QueryFilterInvalidParamException
     * @throws \Logeecom\Infrastructure\ORM\Exceptions\RepositoryClassException
     * @throws \Logeecom\Infrastructure\ORM\Exceptions\RepositoryNotRegisteredException
     */
    public function testQueryWithFiltersString()
    {
        $this->baseTest->testQueueItemMassInsert();

        $this->baseTest->testQueryWithFiltersString();
    }

    /**
     * @throws \Logeecom\Infrastructure\ORM\Exceptions\QueryFilterInvalidParamException
     * @throws \Logeecom\Infrastructure\ORM\Exceptions\RepositoryClassException
     * @throws \Logeecom\Infrastructure\ORM\Exceptions\RepositoryNotRegisteredException
     */
    public function testQueryWithFiltersInt()
    {
        $this->baseTest->testQueueItemMassInsert();

        $this->baseTest->testQueryWithFiltersInt();
    }

    /**
     * @throws \Logeecom\Infrastructure\ORM\Exceptions\QueryFilterInvalidParamException
     * @throws \Logeecom\Infrastructure\ORM\Exceptions\RepositoryClassException
     * @throws \Logeecom\Infrastructure\ORM\Exceptions\RepositoryNotRegisteredException
     */
    public function testQueryWithFiltersAndSort()
    {
        $this->baseTest->testQueueItemMassInsert();

        $this->baseTest->testQueryWithFiltersAndSort();
    }

    /**
     * @throws \Logeecom\Infrastructure\ORM\Exceptions\QueryFilterInvalidParamException
     * @throws \Logeecom\Infrastructure\ORM\Exceptions\RepositoryClassException
     * @throws \Logeecom\Infrastructure\ORM\Exceptions\RepositoryNotRegisteredException
     */
    public function testQueryWithFiltersAndLimit()
    {
        $this->baseTest->testQueueItemMassInsert();

        $this->baseTest->testQueryWithFiltersAndLimit();
    }

    /**
     * @throws \Logeecom\Infrastructure\ORM\Exceptions\RepositoryClassException
     * @throws \Logeecom\Infrastructure\ORM\Exceptions\RepositoryNotRegisteredException
     */
    public function testFindOldestQueuedItems()
    {
        $this->baseTest->testQueueItemMassInsert();

        $this->baseTest->testFindOldestQueuedItems();
    }

    /**
     * @expectedException \Logeecom\Infrastructure\TaskExecution\Exceptions\QueueItemSaveException
     *
     * @throws \Logeecom\Infrastructure\ORM\Exceptions\QueryFilterInvalidParamException
     * @throws \Logeecom\Infrastructure\ORM\Exceptions\RepositoryClassException
     * @throws \Logeecom\Infrastructure\ORM\Exceptions\RepositoryNotRegisteredException
     * @throws \Logeecom\Infrastructure\TaskExecution\Exceptions\QueueItemSaveException
     */
    public function testSaveWithCondition()
    {
        $this->baseTest->testQueueItemMassInsert();

        $this->baseTest->testSaveWithCondition();
    }

    /**
     * @expectedException \Logeecom\Infrastructure\TaskExecution\Exceptions\QueueItemSaveException
     *
     * @throws \Logeecom\Infrastructure\ORM\Exceptions\QueryFilterInvalidParamException
     * @throws \Logeecom\Infrastructure\ORM\Exceptions\RepositoryClassException
     * @throws \Logeecom\Infrastructure\ORM\Exceptions\RepositoryNotRegisteredException
     * @throws \Logeecom\Infrastructure\TaskExecution\Exceptions\QueueItemSaveException
     */
    public function testSaveWithConditionWithNull()
    {
        $this->baseTest->testQueueItemMassInsert();

        $this->baseTest->testSaveWithConditionWithNull();
    }

    /**
     * @expectedException \Logeecom\Infrastructure\ORM\Exceptions\QueryFilterInvalidParamException
     *
     * @throws \Logeecom\Infrastructure\ORM\Exceptions\QueryFilterInvalidParamException
     * @throws \Logeecom\Infrastructure\ORM\Exceptions\RepositoryClassException
     * @throws \Logeecom\Infrastructure\ORM\Exceptions\RepositoryNotRegisteredException
     */
    public function testInvalidQueryFilter()
    {
        $this->baseTest->testInvalidQueryFilter();
    }

    /**
     * @inheritDoc
     *
     * @throws \Logeecom\Infrastructure\ORM\Exceptions\RepositoryClassException
     * @throws \Logeecom\Infrastructure\TaskExecution\Exceptions\TaskRunnerStatusStorageUnavailableException
     */
    protected function setUp()
    {
        $this->baseTest->setUp();
    }

    /**
     * @inheritDoc
     */
    protected function tearDown()
    {
        $this->baseTest->tearDown();
    }
}
