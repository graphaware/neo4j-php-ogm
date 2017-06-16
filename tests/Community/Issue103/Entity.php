<?php

namespace GraphAware\Neo4j\OGM\Tests\Community\Issue103;

use Doctrine\Common\Collections\ArrayCollection;
use GraphAware\Neo4j\OGM\Annotations as OGM;

/**
 * @OGM\Node(label="Entity")
 */
class Entity
{
    /**
     * @OGM\GraphId()
     * @var int
     */
    protected $id;

    /**
     * @OGM\Property(type="string")
     * @var string
     */
    protected $name;


    /**
     * @OGM\Relationship(type="HAS_CONTEXT", direction="OUTGOING", targetEntity="Context", collection=true, mappedBy="entity")
     * @var ArrayCollection|Context[];
     */
    protected $contexts;

    public function __construct($name)
    {
        $this->name = $name;

        $this->contexts = new ArrayCollection();
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
     * @return \Doctrine\Common\Collections\ArrayCollection|Context[];
     */
    public function getContexts()
    {
        return $this->contexts;
    }

    /**
     * @param Context $context
     */
    public function addContext(Context $context)
    {
        if (!$this->contexts->contains($context)) {
            $this->contexts->add($context);
        }
    }

    /**
     * @param Context $context
     */
    public function removeContext(Context $context)
    {
        if ($this->contexts->contains($context)) {
            $this->contexts->removeElement($context);
        }
    }
}