<?php

namespace GraphAware\Neo4j\OGM\Tests\Integration\Models\ManyToOne;

use GraphAware\Neo4j\OGM\Annotations as OGM;

/**
 * Class Bag
 * @package GraphAware\Neo4j\OGM\Tests\Integration\Models\ManyToOne
 *
 * @OGM\Node(label="Bag")
 */
class Bag
{
    /**
     * @var int
     *
     * @OGM\GraphId()
     */
    protected $id;

    /**
     * @var string
     *
     * @OGM\Property()
     */
    protected $brand;

    /**
     * @var Woman
     *
     * @OGM\Relationship(type="OWNS_BAG", direction="INCOMING", mappedBy="bags", targetEntity="Woman")
     */
    protected $owner;

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
    public function getBrand()
    {
        return $this->brand;
    }

    /**
     * @param string $brand
     */
    public function setBrand($brand)
    {
        $this->brand = $brand;
    }

    /**
     * @return Woman
     */
    public function getOwner()
    {
        return $this->owner;
    }

    /**
     * @param Woman $owner
     */
    public function setOwner($owner)
    {
        $this->owner = $owner;
    }


}