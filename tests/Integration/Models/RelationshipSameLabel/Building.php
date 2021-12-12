<?php

/*
 * This file is part of the GraphAware Neo4j PHP OGM package.
 *
 * (c) GraphAware Ltd <info@graphaware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace GraphAware\Neo4j\OGM\Tests\Integration\Models\RelationshipSameLabel;

use GraphAware\Neo4j\OGM\Annotations as OGM;
use GraphAware\Neo4j\OGM\Common\Collection;

/**
 * Class Building.
 *
 * @OGM\Node(labels={"Building"})
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
     * @OGM\Relationship(type="Located", direction="INCOMING", mappedBy="building", collection=true, targetEntity="Equipment")
     *
     * @var Collection|Equipment[]
     */
    protected $equipments;

    /**
     * @OGM\Relationship(type="Located", direction="INCOMING", mappedBy="building", collection=true, targetEntity="Room")
     *
     * @var Collection|Room[]
     */
    protected $rooms;

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
    public function getEquipments()
    {
        return $this->equipments;
    }

    /**
     * @return Collection
     */
    public function getRooms()
    {
        return $this->rooms;
    }
}
