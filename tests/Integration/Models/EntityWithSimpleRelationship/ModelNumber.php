<?php

/*
 * This file is part of the GraphAware Neo4j PHP OGM package.
 *
 * (c) GraphAware Ltd <info@graphaware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace GraphAware\Neo4j\OGM\Tests\Integration\Models\EntityWithSimpleRelationship;

use GraphAware\Neo4j\OGM\Annotations as OGM;

/**
 * Class ModelNumber.
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
