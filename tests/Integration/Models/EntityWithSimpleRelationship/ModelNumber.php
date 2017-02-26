<?php

namespace GraphAware\Neo4j\OGM\Tests\Integration\Models\EntityWithSimpleRelationship;

use GraphAware\Neo4j\OGM\Annotations as OGM;

/**
 * Class ModelNumber.
 *
 * @package GraphAware\Neo4j\OGM\Tests\Integration\Models\EntityWithSimpleRelationship
 *
 * @OGM\Node(label="ModelNumber")
 */
class ModelNumber
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
    protected $number;

    /**
     * @OGM\Relationship(type="HAS_MODEL_NUMBER", direction="INCOMING", targetEntity="Car")
     *
     * @var Car
     */
    protected $carReference;

    public function __construct($number)
    {
        $this->number = $number;
    }

    /**
     * @return mixed
     */
    public function getNumber()
    {
        return $this->number;
    }

    /**
     * @param mixed $number
     */
    public function setNumber($number)
    {
        $this->number = $number;
    }

    /**
     * @return Car
     */
    public function getCarReference()
    {
        return $this->carReference;
    }
}
