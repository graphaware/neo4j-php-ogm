<?php

namespace GraphAware\Neo4j\OGM\Tests\Integration\Models\RelationshipCollection;

use GraphAware\Neo4j\OGM\Annotations as OGM;
use GraphAware\Neo4j\OGM\Common\Collection;

/**
 * Class Building.
 *
 * @package GraphAware\Neo4j\OGM\Tests\Integration\Models\RelationshipCollection
 *
 * @OGM\Node(label="Building")
 */
class Building
{
    /**
     * @OGM\GraphId()
     *
     * @var int
     */
    protected $id;

    /**
     * @OGM\Relationship(type="HAS_FLOOR", direction="OUTGOING", mappedBy="building", collection=true, targetEntity="Floor")
     *
     * @var Collection|Floor[]
     */
    protected $floors;

    public function __construct()
    {
        $this->floors = new Collection();
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return Collection
     */
    public function getFloors()
    {
        return $this->floors;
    }
}
