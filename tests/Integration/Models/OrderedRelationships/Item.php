<?php

/*
 * This file is part of the GraphAware Neo4j PHP OGM package.
 *
 * (c) GraphAware Ltd <info@graphaware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace GraphAware\Neo4j\OGM\Tests\Integration\Models\OrderedRelationships;

use GraphAware\Neo4j\OGM\Annotations as OGM;
use GraphAware\Neo4j\OGM\Common\Collection;

/**
 * Class Item.
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
