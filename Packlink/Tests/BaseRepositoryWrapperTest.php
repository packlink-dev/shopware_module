<?php

namespace Packlink\Tests;

require_once __DIR__ . '/../vendor/autoload.php';

use Packlink\Tests\TestComponents\BaseRepositoryTestAdapter;
use PHPUnit\Framework\TestCase;

class BaseRepositoryWrapperTest extends TestCase
{
    protected static $ensureLoadedPlugins = [
        'Packlink' => [],
    ];
    /**
     * @var \Packlink\Tests\TestComponents\BaseRepositoryTestAdapter
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
        $this->baseTest = new BaseRepositoryTestAdapter(...func_get_args());
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
     * @throws \Packlink\Core\Infrastructure\ORM\Exceptions\RepositoryNotRegisteredException
     */
    public function testRegisteredRepositories()
    {
        $this->baseTest->testRegisteredRepositories();
    }

    /**
     * @throws \Packlink\Core\Infrastructure\ORM\Exceptions\RepositoryNotRegisteredException
     */
    public function testStudentMassInsert()
    {
        $this->baseTest->testStudentMassInsert();
    }

    /**
     * @throws \Packlink\Core\Infrastructure\ORM\Exceptions\QueryFilterInvalidParamException
     * @throws \Packlink\Core\Infrastructure\ORM\Exceptions\RepositoryNotRegisteredException
     */
    public function testStudentUpdate()
    {
        $this->baseTest->testStudentUpdate();
    }

    /**
     * @throws \Packlink\Core\Infrastructure\ORM\Exceptions\RepositoryNotRegisteredException
     */
    public function testQueryAllStudents()
    {
        $this->baseTest->testQueryAllStudents();
    }

    /**
     * @throws \Packlink\Core\Infrastructure\ORM\Exceptions\QueryFilterInvalidParamException
     * @throws \Packlink\Core\Infrastructure\ORM\Exceptions\RepositoryNotRegisteredException
     */
    public function testQueryWithFiltersString()
    {
        $this->baseTest->testQueryWithFiltersString();
    }

    /**
     * @throws \Packlink\Core\Infrastructure\ORM\Exceptions\QueryFilterInvalidParamException
     * @throws \Packlink\Core\Infrastructure\ORM\Exceptions\RepositoryNotRegisteredException
     */
    public function testQueryWithFiltersInt()
    {
        $this->baseTest->testQueryWithFiltersInt();
    }

    /**
     * @throws \Packlink\Core\Infrastructure\ORM\Exceptions\QueryFilterInvalidParamException
     * @throws \Packlink\Core\Infrastructure\ORM\Exceptions\RepositoryNotRegisteredException
     */
    public function testQueryWithOr()
    {
        $this->baseTest->testQueryWithOr();
    }

    /**
     * @throws \Packlink\Core\Infrastructure\ORM\Exceptions\QueryFilterInvalidParamException
     * @throws \Packlink\Core\Infrastructure\ORM\Exceptions\RepositoryNotRegisteredException
     */
    public function testQueryWithAndAndOr()
    {
        $this->baseTest->testQueryWithAndAndOr();
    }

    /**
     * @throws \Packlink\Core\Infrastructure\ORM\Exceptions\QueryFilterInvalidParamException
     * @throws \Packlink\Core\Infrastructure\ORM\Exceptions\RepositoryNotRegisteredException
     */
    public function testQueryWithNotEquals()
    {
        $this->baseTest->testQueryWithNotEquals();
    }

    /**
     * @throws \Packlink\Core\Infrastructure\ORM\Exceptions\QueryFilterInvalidParamException
     * @throws \Packlink\Core\Infrastructure\ORM\Exceptions\RepositoryNotRegisteredException
     */
    public function testQueryWithGreaterThan()
    {
        $this->baseTest->testQueryWithGreaterThan();
    }

    /**
     * @throws \Packlink\Core\Infrastructure\ORM\Exceptions\QueryFilterInvalidParamException
     * @throws \Packlink\Core\Infrastructure\ORM\Exceptions\RepositoryNotRegisteredException
     */
    public function testQueryWithLessThan()
    {
        $this->baseTest->testQueryWithLessThan();
    }

    /**
     * @throws \Packlink\Core\Infrastructure\ORM\Exceptions\QueryFilterInvalidParamException
     * @throws \Packlink\Core\Infrastructure\ORM\Exceptions\RepositoryNotRegisteredException
     */
    public function testQueryWithGreaterEqualThan()
    {
        $this->baseTest->testQueryWithGreaterEqualThan();
    }

    /**
     * @throws \Packlink\Core\Infrastructure\ORM\Exceptions\QueryFilterInvalidParamException
     * @throws \Packlink\Core\Infrastructure\ORM\Exceptions\RepositoryNotRegisteredException
     */
    public function testQueryWithLessOrEqualThan()
    {
        $this->baseTest->testQueryWithLessOrEqualThan();
    }

    /**
     * @throws \Packlink\Core\Infrastructure\ORM\Exceptions\QueryFilterInvalidParamException
     * @throws \Packlink\Core\Infrastructure\ORM\Exceptions\RepositoryNotRegisteredException
     */
    public function testQueryWithCombinedComparisonOperators()
    {
        $this->baseTest->testQueryWithCombinedComparisonOperators();
    }

    /**
     * @throws \Packlink\Core\Infrastructure\ORM\Exceptions\QueryFilterInvalidParamException
     * @throws \Packlink\Core\Infrastructure\ORM\Exceptions\RepositoryNotRegisteredException
     */
    public function testQueryWithInOperator()
    {
        $this->baseTest->testQueryWithInOperator();
    }

    /**
     * @throws \Packlink\Core\Infrastructure\ORM\Exceptions\QueryFilterInvalidParamException
     * @throws \Packlink\Core\Infrastructure\ORM\Exceptions\RepositoryNotRegisteredException
     */
    public function testQueryWithNotInOperator()
    {
        $this->baseTest->testQueryWithNotInOperator();
    }

    /**
     * @throws \Packlink\Core\Infrastructure\ORM\Exceptions\QueryFilterInvalidParamException
     * @throws \Packlink\Core\Infrastructure\ORM\Exceptions\RepositoryNotRegisteredException
     */
    public function testQueryWithLikeOperator()
    {
        $this->baseTest->testQueryWithLikeOperator();
    }

    /**
     * @throws \Packlink\Core\Infrastructure\ORM\Exceptions\QueryFilterInvalidParamException
     * @throws \Packlink\Core\Infrastructure\ORM\Exceptions\RepositoryNotRegisteredException
     */
    public function testQueryWithFiltersAndSort()
    {
        $this->baseTest->testQueryWithFiltersAndSort();
    }

    /**
     * @throws \Packlink\Core\Infrastructure\ORM\Exceptions\QueryFilterInvalidParamException
     * @throws \Packlink\Core\Infrastructure\ORM\Exceptions\RepositoryNotRegisteredException
     */
    public function testQueryWithUnknownFieldSort()
    {
        $this->baseTest->testQueryWithUnknownFieldSort();
    }

    /**
     * @throws \Packlink\Core\Infrastructure\ORM\Exceptions\QueryFilterInvalidParamException
     * @throws \Packlink\Core\Infrastructure\ORM\Exceptions\RepositoryNotRegisteredException
     */
    public function testQueryWithUnIndexedFieldSort()
    {
        $this->baseTest->testQueryWithUnIndexedFieldSort();
    }

    /**
     * @throws \Packlink\Core\Infrastructure\ORM\Exceptions\QueryFilterInvalidParamException
     * @throws \Packlink\Core\Infrastructure\ORM\Exceptions\RepositoryNotRegisteredException
     */
    public function testQueryWithIdFieldSort()
    {
        $this->baseTest->testQueryWithIdFieldSort();
    }

    /**
     * @throws \Packlink\Core\Infrastructure\ORM\Exceptions\QueryFilterInvalidParamException
     * @throws \Packlink\Core\Infrastructure\ORM\Exceptions\RepositoryNotRegisteredException
     */
    public function testQueryWithFiltersAndLimit()
    {
        $this->baseTest->testQueryWithFiltersAndLimit();
    }

    /**
     * @inheritDoc
     *
     * @throws \Packlink\Core\Infrastructure\ORM\Exceptions\RepositoryClassException
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
