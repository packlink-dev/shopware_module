<?php

namespace Packlink\Tests\TestComponents\Components;

use Packlink\Repositories\BaseRepository;

class TestBaseRepository extends BaseRepository
{
    protected static $doctrineModel = TestEntity::class;
}