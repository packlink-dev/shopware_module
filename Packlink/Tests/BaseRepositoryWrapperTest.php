<?php

namespace Packlink;

require_once __DIR__ . '/../vendor/autoload.php';

use Packlink\Tests\TestComponents\BaseRepositoryTestAddatper;
use Shopware\Components\Test\Plugin\TestCase;

class BaseRepositoryWrapperTest extends TestCase
{
    protected static $ensureLoadedPlugins = [
        'Packlink' => []
    ];
    /**
     * @var \Packlink\Tests\TestComponents\BaseRepositoryTestAddatper
     */
    protected $baseTest;

    /**
     * BaseRepositoryWrapperTest constructor.
     *
     * @throws \Exception
     */
    public function __construct()
    {
        parent::__construct(...func_get_args());
        $this->baseTest = new BaseRepositoryTestAddatper(...func_get_args());
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
        if (method_exists($this->baseTest, $name) && is_callable([$this->baseTest, $name])) {
            $this->baseTest->$name(...$arguments);
        }
    }

    /**
     * @throws \Logeecom\Infrastructure\ORM\Exceptions\RepositoryNotRegisteredException
     */
    public function testRegisteredRepositories()
    {
        $this->baseTest->testRegisteredRepositories();
    }

    /**
     * @throws \Logeecom\Infrastructure\ORM\Exceptions\RepositoryNotRegisteredException
     */
    public function testStudentMassInsert()
    {
        $this->baseTest->testStudentMassInsert();
    }

    /**
     * @throws \Logeecom\Infrastructure\ORM\Exceptions\QueryFilterInvalidParamException
     * @throws \Logeecom\Infrastructure\ORM\Exceptions\RepositoryNotRegisteredException
     */
    public function testStudentUpdate()
    {
        $this->baseTest->testStudentUpdate();
    }

    /**
     * @throws \Logeecom\Infrastructure\ORM\Exceptions\RepositoryNotRegisteredException
     */
    public function testQueryAllStudents()
    {
        $this->baseTest->testQueryAllStudents();
    }

    /**
     * @throws \Logeecom\Infrastructure\ORM\Exceptions\QueryFilterInvalidParamException
     * @throws \Logeecom\Infrastructure\ORM\Exceptions\RepositoryNotRegisteredException
     */
    public function testQueryWithFiltersString()
    {
        $this->baseTest->testQueryWithFiltersString();
    }

    /**
     * @throws \Logeecom\Infrastructure\ORM\Exceptions\QueryFilterInvalidParamException
     * @throws \Logeecom\Infrastructure\ORM\Exceptions\RepositoryNotRegisteredException
     */
    public function testQueryWithFiltersInt()
    {
        $this->baseTest->testQueryWithFiltersInt();
    }

    /**
     * @throws \Logeecom\Infrastructure\ORM\Exceptions\QueryFilterInvalidParamException
     * @throws \Logeecom\Infrastructure\ORM\Exceptions\RepositoryNotRegisteredException
     */
    public function testQueryWithFiltersAndSort()
    {
        $this->baseTest->testQueryWithFiltersAndSort();
    }

    /**
     * @throws \Logeecom\Infrastructure\ORM\Exceptions\QueryFilterInvalidParamException
     * @throws \Logeecom\Infrastructure\ORM\Exceptions\RepositoryNotRegisteredException
     */
    public function testQueryWithUnknownFieldSort()
    {
        $this->baseTest->testQueryWithUnknownFieldSort();
    }

    /**
     * @throws \Logeecom\Infrastructure\ORM\Exceptions\QueryFilterInvalidParamException
     * @throws \Logeecom\Infrastructure\ORM\Exceptions\RepositoryNotRegisteredException
     */
    public function testQueryWithUnIndexedFieldSort()
    {
        $this->baseTest->testQueryWithUnIndexedFieldSort();
    }

    /**
     * @throws \Logeecom\Infrastructure\ORM\Exceptions\QueryFilterInvalidParamException
     * @throws \Logeecom\Infrastructure\ORM\Exceptions\RepositoryNotRegisteredException
     */
    public function testQueryWithIdFieldSort()
    {
        $this->baseTest->testQueryWithIdFieldSort();
    }

    /**
     * @throws \Logeecom\Infrastructure\ORM\Exceptions\QueryFilterInvalidParamException
     * @throws \Logeecom\Infrastructure\ORM\Exceptions\RepositoryNotRegisteredException
     */
    public function testQueryWithFiltersAndLimit()
    {
        $this->baseTest->testQueryWithFiltersAndLimit();
    }

    /**
     * @inheritDoc
     *
     * @throws \Logeecom\Infrastructure\ORM\Exceptions\RepositoryClassException
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
