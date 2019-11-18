<?php

namespace Packlink\Tests\TestComponents\Components;

use Packlink\Repositories\QueueItemRepository;

class TestQueueItemRepository extends QueueItemRepository
{
    /**
     * Fully qualified name of this class.
     */
    const THIS_CLASS_NAME = __CLASS__;

    protected static $doctrineModel = TestEntity::class;

    /**
     * Retrieves db_name for DBAL.
     *
     * @return string
     */
    protected function getDbName()
    {
        return 'test_packlink_entity';
    }
}