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
 * Class House.
 *
 * @OGM\Node(label="House")
 */
class House
{
    /**
     * @OGM\GraphId()
     *
     * @var int
     */
    protected $id;

    /**
     * @OGM\Relationship(relationshipEntity="Acquisition", type="ACQUIRED", direction="INCOMING", mappedBy="house")
     *
     * @var Acquisition
     */
    protected $acquisition;

    /**
     * @OGM\Property()
     *
     * @var string
     */
    protected $address;

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return Acquisition
     */
    public function getAcquisition()
    {
        return $this->acquisition;
    }

    /**
     * @param Acquisition $acquisition
     */
    public function setAcquisition($acquisition)
    {
        $this->acquisition = $acquisition;
    }

    /**
     * @return string
     */
    public function getAddress()
    {
        return $this->address;
    }

    /**
     * @param string $address
     */
    public function setAddress($address)
    {
        $this->address = $address;
    }
}
