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
use GraphAware\Neo4j\OGM\Common\Collection;

/**
 * Class Owner.
 *
 * @OGM\Node(label="Owner")
 */
class Owner
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
     * @OGM\Relationship(relationshipEntity="Acquisition", type="ACQUIRED", direction="OUTGOING", collection=true, mappedBy="owner")
     *
     * @var Acquisition[]|Collection
     */
    protected $acquisitions;

    public function __construct($name)
    {
        $this->name = $name;
        $this->acquisitions = new Collection();
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
     * @return Collection|Acquisition[]
     */
    public function getAcquisitions()
    {
        return $this->acquisitions;
    }

    /**
     * @param string $name
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    public function getAcquisitionByAddress($address)
    {
        foreach ($this->getAcquisitions() as $acquisition) {
            if ($acquisition->getHouse()->getAddress() === $address) {
                return $acquisition;
            }
        }

        return null;
    }
}
