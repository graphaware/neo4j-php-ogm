<?php

namespace GraphAware\Neo4j\OGM\Tests\Integration\SimpleEntity\Model;

use GraphAware\Neo4j\OGM\Annotations as OGM;

/**
 * Class Human
 * @package GraphAware\Neo4j\OGM\Tests\Integration\SimpleEntity\Model
 *
 * @OGM\Node(label="Human")
 */
class Human
{
    /**
     * @OGM\GraphId()
     *
     * @var int
     */
    protected $id;

    /**
     * @OGM\Property(type="string")
     *
     * @var string
     */
    protected $name;

    /**
     * @OGM\Label(name="Organic")
     *
     * @var bool
     */
    protected $isOrganic = false;

    public function __construct($name)
    {
        $this->name = $name;
    }

    /**
     * @return mixed
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return mixed
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @return mixed
     */
    public function getIsOrganic()
    {
        return $this->isOrganic;
    }

    /**
     * @param mixed $name
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    /**
     * @param mixed $isOrganic
     */
    public function setIsOrganic($isOrganic)
    {
        $this->isOrganic = $isOrganic;
    }
}