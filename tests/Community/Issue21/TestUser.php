<?php

namespace GraphAware\Neo4j\OGM\Tests\Community\Issue21;

use GraphAware\Neo4j\OGM\Annotations as OGM;
use GraphAware\Neo4j\OGM\Common\Collection;

/**
 * Class TestUser.
 *
 * @package GraphAware\Neo4j\OGM\Tests\Integration\Community\Issue21
 *
 * @OGM\Node(label="TestUser")
 */
class TestUser
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
     * @OGM\Relationship(type="SPONSOR_USER", direction="OUTGOING", targetEntity="TestUser", collection=true, mappedBy="sponsoredBy")
     *
     * @var Collection|TestUser[]
     */
    protected $sponsoredChildren;

    /**
     * @OGM\Relationship(type="SPONSOR_USER", direction="INCOMING", targetEntity="TestUser")
     *
     * @var TestUser
     */
    protected $sponsoredBy;

    public function __construct($name)
    {
        $this->sponsoredChildren = new Collection();
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
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    /**
     * @return Collection|TestUser[]
     */
    public function getSponsoredChildren()
    {
        return $this->sponsoredChildren;
    }

    /**
     * @param Collection|TestUser[] $sponsoredChildren
     */
    public function setSponsoredChildren($sponsoredChildren)
    {
        $this->sponsoredChildren = $sponsoredChildren;
    }

    public function addSponsoredChild(TestUser $child)
    {
        if (!$this->getSponsoredChildren()->contains($child)) {
            $this->getSponsoredChildren()->add($child);
        }
    }

    /**
     * @return TestUser
     */
    public function getSponsoredBy()
    {
        return $this->sponsoredBy;
    }

    /**
     * @param TestUser $sponsoredBy
     */
    public function setSponsoredBy($sponsoredBy)
    {
        $this->sponsoredBy = $sponsoredBy;
    }
}
