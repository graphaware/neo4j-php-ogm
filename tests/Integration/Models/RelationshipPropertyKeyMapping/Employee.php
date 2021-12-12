<?php

/*
 * This file is part of the GraphAware Neo4j PHP OGM package.
 *
 * (c) GraphAware Ltd <info@graphaware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace GraphAware\Neo4j\OGM\Tests\Integration\Models\RelationshipPropertyKeyMapping;

use GraphAware\Neo4j\OGM\Annotations as OGM;

/**
 * Class Employee.
 *
 * @OGM\Node(labels={"Employee"})
 */
class Employee
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
     * @OGM\Relationship(relationshipEntity="UsesDevice", type="USES_DEVICE", direction="OUTGOING", mappedBy="employee")
     *
     * @var UsesDevice
     */
    protected $device;

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
     * @param string $name
     *
     * @return Employee
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * @return UsesDevice
     */
    public function getDevice()
    {
        return $this->device;
    }

    /**
     * @param UsesDevice $device
     *
     * @return Employee
     */
    public function setDevice(UsesDevice $device)
    {
        $this->device = $device;

        return $this;
    }
}
