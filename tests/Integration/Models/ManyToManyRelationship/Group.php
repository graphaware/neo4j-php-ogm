<?php

namespace GraphAware\Neo4j\OGM\Tests\Integration\Models\ManyToManyRelationship;

use GraphAware\Neo4j\OGM\Annotations as OGM;
use GraphAware\Neo4j\OGM\Common\Collection;

/**
 * Class Group.
 *
 * @package GraphAware\Neo4j\OGM\Tests\Integration\Models\ManyToManyRelationship
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

    public function __construct($name)
    {
        $this->name = $name;
        $this->users = new Collection();
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
     * @param string $name
     */
    public function setName($name)
    {
        $this->name = $name;
    }
}
