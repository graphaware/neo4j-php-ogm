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
 * Class Device.
 *
 * @OGM\Node(labels={"Device"})
 */
class Device
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
     * @OGM\Relationship(relationshipEntity="UsesDevice", type="USES_DEVICE", direction="INCOMING", mappedBy="device")
     *
     * @var UsesDevice
     */
    protected $employee;

    /**
     * Device constructor.
     *
     * @param string $name
     */
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
     * @return Device
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * @return UsesDevice
     */
    public function getEmployee()
    {
        return $this->employee;
    }

    /**
     * @param UsesDevice $employee
     *
     * @return Device
     */
    public function setEmployee($employee)
    {
        $this->employee = $employee;

        return $this;
    }
}
