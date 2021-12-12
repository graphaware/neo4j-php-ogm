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
 * Class Group.
 *
 * @OGM\Node(label="Group")
 */
class Group
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
     * @OGM\Relationship(type="IN_GROUP", direction="INCOMING", mappedBy="groups", collection=true, targetEntity="User")
     *
     * @var Collection|User[]
     */
    protected $users;

    /**
     * @OGM\Relationship(type="HAS_PERMISSION", direction="OUTGOING", mappedBy="groups", collection=true, targetEntity="Permission")
     *
     * @var Collection|Permission[]
     */
    protected $permissions;

    /**
     * @OGM\Relationship(type="HAS_PERMISSION", direction="OUTGOING", mappedBy="groups", collection=false, targetEntity="Permission")
     *
     * @var Permission
     */
    protected $permission;


    public function __construct($name)
    {
        $this->name = $name;
        $this->users = new Collection();
        $this->permissions = new Collection();
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
     * @return Collection|User[]
     */
    public function getUsers()
    {
        return $this->users;
    }

    /**
     * @return Collection|Permission[]
     */
    public function getPermissions()
    {
        return $this->permissions;
    }

    /**
     * @return Permission
     */
    public function getPermission()
    {
        return $this->permission;
    }

    /**
     * @param string $name
     */
    public function setName($name)
    {
        $this->name = $name;
    }
}
