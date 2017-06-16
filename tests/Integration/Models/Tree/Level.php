<?php

namespace GraphAware\Neo4j\OGM\Tests\Integration\Models\Tree;

use Doctrine\Common\Collections\ArrayCollection;
use GraphAware\Neo4j\OGM\Annotations as OGM;
use GraphAware\Neo4j\OGM\Common\Collection;

/**
 *
 * @OGM\Node(label="Level")
 */
class Level
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
     * @OGM\Property(type="string")
     */
    protected $code;

    /**
     * @var Level
     *
     * @OGM\Relationship(type="PARENT_LEVEL", direction="OUTGOING", targetEntity="Level", mappedBy="children")
     */
    protected $parent;

    /**
     * @var Level[]|ArrayCollection
     *
     * @OGM\Relationship(type="PARENT_LEVEL", direction="INCOMING", targetEntity="Level", collection=true, mappedBy="parent")
     */
    protected $children;

    public function __construct($code)
    {
        $this->code = $code;
        $this->children = new Collection();
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
    public function getCode()
    {
        return $this->code;
    }

    /**
     * @return Level
     */
    public function getParent()
    {
        return $this->parent;
    }

    /**
     * @return ArrayCollection|Level[]
     */
    public function getChildren()
    {
        return $this->children;
    }

    /**
     * @param Level $parent
     */
    public function setParent($parent)
    {
        $this->parent = $parent;
        $parent->getChildren()->add($this);
    }

    public function getChild($code)
    {
        foreach ($this->getChildren() as $child) {
            if ($child->getCode() === $code) {
                return $child;
            }
        }

        throw new \InvalidArgumentException(sprintf('Child with code "%s" not found', $code));
    }
}