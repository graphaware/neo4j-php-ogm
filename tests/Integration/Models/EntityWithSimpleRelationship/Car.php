<?php

namespace GraphAware\Neo4j\OGM\Tests\Integration\Models\EntityWithSimpleRelationship;

use GraphAware\Neo4j\OGM\Annotations as OGM;

/**
 * Class Car.
 *
 * @package GraphAware\Neo4j\OGM\Tests\Integration\Models\EntityWithSimpleRelationship
 *
 * @OGM\Node(label="Car")
 */
class Car
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
    protected $model;

    /**
     * @OGM\Relationship(type="OWNS", direction="INCOMING", targetEntity="Person", mappedBy="car")
     *
     * @var Person
     */
    protected $owner;

    /**
     * @OGM\Relationship(type="HAS_MODEL_NUMBER", direction="OUTGOING", targetEntity="ModelNumber", mappedBy="carReference")
     *
     * @var ModelNumber
     */
    protected $modelNumber;

    public function __construct($model, Person $owner = null)
    {
        $this->model = $model;
        $this->owner = $owner;
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
    public function getModel()
    {
        return $this->model;
    }

    /**
     * @return Person
     */
    public function getOwner()
    {
        return $this->owner;
    }

    /**
     * @param string $model
     */
    public function setModel($model)
    {
        $this->model = $model;
    }

    /**
     * @return ModelNumber
     */
    public function getModelNumber()
    {
        return $this->modelNumber;
    }

    /**
     * @param ModelNumber $modelNumber
     */
    public function setModelNumber($modelNumber)
    {
        $this->modelNumber = $modelNumber;
    }
}
