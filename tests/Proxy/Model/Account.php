<?php

namespace GraphAware\Neo4j\OGM\Tests\Proxy\Model;

use GraphAware\Neo4j\OGM\Annotations as OGM;

/**
 * @OGM\Node(label="Account")
 */
class Account
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
    protected $credentials;

    /**
     * @var User
     *
     * @OGM\Relationship(type="HAS_ACCOUNT", direction="INCOMING", mappedBy="account", targetEntity="User")
     */
    protected $user;

    /**
     * @var Group
     *
     * @OGM\Relationship(type="IN_GROUP", direction="OUTGOING", targetEntity="Group", mappedBy="accounts")
     */
    protected $group;

    public function __construct()
    {
        $this->credentials = str_repeat('x'.time(), 2);
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
    public function getCredentials()
    {
        return $this->credentials;
    }

    /**
     * @return User
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * @return Group
     */
    public function getGroup()
    {
        return $this->group;
    }

    public function setGroup(Group $group)
    {
        $this->group = $group;
    }
}