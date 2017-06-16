<?php

/*
 * This file is part of the GraphAware Neo4j PHP OGM package.
 *
 * (c) GraphAware Ltd <info@graphaware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace GraphAware\Neo4j\OGM\Tests\Integration\Models\OneToManyRE;

use GraphAware\Neo4j\OGM\Annotations as OGM;

/**
 * Class Acquisition.
 *
 * @OGM\RelationshipEntity(type="ACQUIRED")
 */
class Acquisition
{
    /**
     * @OGM\GraphId()
     *
     * @var int
     */
    protected $id;

    /**
     * @OGM\StartNode(targetEntity="Owner")
     *
     * @var Owner
     */
    protected $owner;

    /**
     * @OGM\EndNode(targetEntity="House")
     *
     * @var House
     */
    protected $house;

    /**
     * @OGM\Property()
     *
     * @var int
     */
    protected $year;

    /**
     * Acquisition constructor.
     *
     * @param Owner $owner
     * @param House $house
     * @param int   $year
     */
    public function __construct(Owner $owner, House $house, $year)
    {
        $this->owner = $owner;
        $this->house = $house;
        $this->year = $year;
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return Owner
     */
    public function getOwner()
    {
        return $this->owner;
    }

    /**
     * @param Owner $owner
     */
    public function setOwner($owner)
    {
        $this->owner = $owner;
    }

    /**
     * @return House
     */
    public function getHouse()
    {
        return $this->house;
    }

    /**
     * @param House $house
     */
    public function setHouse($house)
    {
        $this->house = $house;
    }

    /**
     * @return int
     */
    public function getYear()
    {
        return $this->year;
    }

    /**
     * @param int $year
     */
    public function setYear($year)
    {
        $this->year = $year;
    }
}
