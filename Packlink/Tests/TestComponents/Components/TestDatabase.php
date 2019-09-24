<?php

namespace Packlink\Tests\TestComponents\Components;

use Packlink\Bootstrap\Database;

class TestDatabase extends Database
{
    public function install()
    {
        $this->schemaTool->updateSchema($this->getClassesMetaData(), true);
    }

    public function uninstall()
    {
        $this->schemaTool->dropSchema($this->getClassesMetaData());
    }

    protected function getClassesMetaData()
    {
        return [
            $this->entityManager->getClassMetadata(TestEntity::class)
        ];
    }
}
