<?php

namespace Packlink\Models;

use Doctrine\ORM\Mapping\Index;
use Shopware\Components\Model\ModelEntity;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(
 *     name="packlink_entity",
 *     indexes={
 *              @Index(name="index_1", columns={"index_1"}),
 *              @Index(name="index_2", columns={"index_2"}),
 *              @Index(name="index_3", columns={"index_3"}),
 *              @Index(name="index_4", columns={"index_4"}),
 *              @Index(name="index_5", columns={"index_5"}),
 *              @Index(name="index_6", columns={"index_6"}),
 *              @Index(name="index_7", columns={"index_7"})
 *          }
 *      )
 */
class PacklinkEntity extends ModelEntity
{
    /**
     * @var integer $id
     *
     * @ORM\Column(name="id", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;
    /**
     * @var string $type
     *
     * @ORM\Column(name="type", type="string", length=128, nullable=false)
     */
    private $type;
    /**
     * @var string $index1
     *
     * @ORM\Column(name="index_1", type="string", length=255, nullable=true)
     */
    private $index1;
    /**
     * @var string $index2
     *
     * @ORM\Column(name="index_2", type="string", length=255, nullable=true)
     */
    private $index2;
    /**
     * @var string $index3
     *
     * @ORM\Column(name="index_3", type="string", length=255, nullable=true)
     */
    private $index3;
    /**
     * @var string $index4
     *
     * @ORM\Column(name="index_4", type="string", length=255, nullable=true)
     */
    private $index4;
    /**
     * @var string $index5
     *
     * @ORM\Column(name="index_5", type="string", length=255, nullable=true)
     */
    private $index5;
    /**
     * @var string $index6
     *
     * @ORM\Column(name="index_6", type="string", length=255, nullable=true)
     */
    private $index6;
    /**
     * @var string $index7
     *
     * @ORM\Column(name="index_7", type="string", length=255, nullable=true)
     */
    private $index7;
    /**
     * @var string $data
     *
     * @ORM\Column(name="data", type="text", nullable=false)
     */
    private $data;

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @param string $type
     */
    public function setType($type)
    {
        $this->type = $type;
    }

    /**
     * @return string
     */
    public function getIndex1()
    {
        return $this->index1;
    }

    /**
     * @param string $index1
     */
    public function setIndex1($index1)
    {
        $this->index1 = $index1;
    }

    /**
     * @return string
     */
    public function getIndex2()
    {
        return $this->index2;
    }

    /**
     * @param string $index2
     */
    public function setIndex2($index2)
    {
        $this->index2 = $index2;
    }

    /**
     * @return string
     */
    public function getIndex3()
    {
        return $this->index3;
    }

    /**
     * @param string $index3
     */
    public function setIndex3($index3)
    {
        $this->index3 = $index3;
    }

    /**
     * @return string
     */
    public function getIndex4()
    {
        return $this->index4;
    }

    /**
     * @param string $index4
     */
    public function setIndex4($index4)
    {
        $this->index4 = $index4;
    }

    /**
     * @return string
     */
    public function getIndex5()
    {
        return $this->index5;
    }

    /**
     * @param string $index5
     */
    public function setIndex5($index5)
    {
        $this->index5 = $index5;
    }

    /**
     * @return string
     */
    public function getIndex6()
    {
        return $this->index6;
    }

    /**
     * @param string $index6
     */
    public function setIndex6($index6)
    {
        $this->index6 = $index6;
    }

    /**
     * @return string
     */
    public function getIndex7()
    {
        return $this->index7;
    }

    /**
     * @param string $index7
     */
    public function setIndex7($index7)
    {
        $this->index7 = $index7;
    }

    /**
     * @return string
     */
    public function getData()
    {
        return $this->data;
    }

    /**
     * @param string $data
     */
    public function setData($data)
    {
        $this->data = $data;
    }
}