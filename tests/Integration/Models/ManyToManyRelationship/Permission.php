<?php

/*
 * This file is part of the GraphAware Neo4j PHP OGM package.
 *
 * (c) GraphAware Ltd <info@graphaware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace GraphAware\Neo4j\OGM\Tests\Integration\Models\ManyToManyRelationship;

use GraphAware\Neo4j\OGM\Annotations as OGM;
use GraphAware\Neo4j\OGM\Common\Collection;

/**
 * Class Permission.
 *
 * @OGM\Node(label="Permission")
 */
class Permission
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
     * @OGM\Relationship(type="HAS_PERMISSION", direction="INCOMING", mappedBy="permissions", collection=true, targetEntity="Group")
     *
     * @var Collection|Group[]
     */
    protected $groups;


    public function __construct($name)
    {
        $this->name = $name;
        $this->groups = new Collection();
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
     * @return Collection|Group[]
     */
    public function getGroups()
    {
        return $this->groups;
    }

    /**
     * @param string $name
     */
    public function setName($name)
    {
        $this->name = $name;
    }
}
