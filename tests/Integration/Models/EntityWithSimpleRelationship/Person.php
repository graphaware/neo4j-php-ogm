<?php

namespace GraphAware\Neo4j\OGM\Tests\Integration\Models\EntityWithSimpleRelationship;

use GraphAware\Neo4j\OGM\Annotations as OGM;

/**
 * Class Person.
 *
 * @package GraphAware\Neo4j\OGM\Tests\Integration\Models\EntityWithSimpleRelationship
 *
 * @OGM\Node(label="Person")
 */
class Person
{
    /**
     * @OGM\GraphId()
     *
     * @var int
     */
    protected $id;

    /**
     * @OGM\Property()
     *
     * @var string
     */
    protected $name;

    /**
     * @OGM\Relationship(type="OWNS", direction="OUTGOING", targetEntity="Car", mappedBy="owner")
     *
     * @var Car
     */
    protected $car;

    public function __construct($name)
    {
        $this->name = $name;
    }

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
    public function getName()
    {
        return $this->name;
    }

    /**
     * @return Car
     */
    public function getCar()
    {
        return $this->car;
    }

    /**
     * @param string $name
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    /**
     * @param Car $car
     */
    public function setCar($car)
    {
        $this->car = $car;
    }
}
