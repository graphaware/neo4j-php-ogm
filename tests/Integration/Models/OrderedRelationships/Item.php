<?php

namespace GraphAware\Neo4j\OGM\Tests\Integration\Models\OrderedRelationships;

use GraphAware\Neo4j\OGM\Annotations as OGM;
use GraphAware\Neo4j\OGM\Common\Collection;

/**
 * Class Item.
 *
 * @package GraphAware\Neo4j\OGM\Tests\Integration\Models\OrderedRelationships
 *
 * @OGM\Node(label="Item")
 */
class Item
{
    /**
     * @OGM\GraphId()
     *
     * @var int
     */
    protected $id;

    /**
     * @OGM\Relationship(type="CLICKS_ON", targetEntity="Click", direction="INCOMING", collection=true)
     * @OGM\OrderBy(property="time", order="ASC")
     *
     * @var Click[]|Collection
     */
    protected $clicks;

    public function __construct()
    {
        $this->clicks = new Collection();
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return Collection|Click[]
     */
    public function getClicks()
    {
        return $this->clicks;
    }
}
