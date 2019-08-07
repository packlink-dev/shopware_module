<?php

namespace Packlink\Tests\TestComponents\Components;

use Packlink\Bootstrap\Database;

class TestDatabase extends Database
{
    protected function getClassesMetaData()
    {
        return [
            $this->entityManager->getClassMetadata(TestEntity::class)
        ];
    }
}