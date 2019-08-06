<?php

namespace Packlink\Bootstrap;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Tools\SchemaTool;
use Packlink\Models\PacklinkEntity;

class Database
{
    /**
     * @var EntityManager
     */
    private $entityManager;
    /**
     * @var \Doctrine\ORM\Tools\SchemaTool
     */
    private $schemaTool;

    /**
     * @param EntityManager $entityManager
     */
    public function __construct(EntityManager $entityManager)
    {
        $this->entityManager = $entityManager;
        $this->schemaTool = new SchemaTool($this->entityManager);
    }

    /**
     * Installs all registered ORM classes
     */
    public function install()
    {
        $this->schemaTool->updateSchema(
            $this->getClassesMetaData(),
            true // make sure to use the save mode
        );
    }

    /**
     * Drops all registered ORM classes
     */
    public function uninstall()
    {
        $this->schemaTool->dropSchema(
            $this->getClassesMetaData()
        );
    }

    /**
     * @return array
     */
    private function getClassesMetaData()
    {
        return [
            $this->entityManager->getClassMetadata(PacklinkEntity::class)
        ];
    }
}