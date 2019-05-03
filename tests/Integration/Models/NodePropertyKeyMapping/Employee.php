<?php

/*
 * This file is part of the GraphAware Neo4j PHP OGM package.
 *
 * (c) GraphAware Ltd <info@graphaware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace GraphAware\Neo4j\OGM\Tests\Integration\Models\NodePropertyKeyMapping;

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
     * @OGM\Property(key="hired_on")
     * @OGM\Convert(type="datetime", options={"format":"timestamp"})
     *
     * @var \DateTimeInterface
     */
    protected $hiredOn;

    /**
     * Employee constructor.
     *
     * @param string $name
     * @param \DateTimeInterface $hiredOn
     */
    public function __construct($name, \DateTimeInterface $hiredOn)
    {
        $this->name = $name;
        $this->hiredOn = $hiredOn;
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
     * @return \DateTimeInterface
     */
    public function getHiredOn()
    {
        return $this->hiredOn;
    }

    /**
     * @param \DateTimeInterface $hiredOn
     *
     * @return Employee
     */
    public function setHiredOn(\DateTimeInterface $hiredOn)
    {
        $this->hiredOn = $hiredOn;

        return $this;
    }
}
