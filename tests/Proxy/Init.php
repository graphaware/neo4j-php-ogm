<?php

namespace GraphAware\Neo4j\OGM\Tests\Proxy;

use GraphAware\Neo4j\OGM\Annotations as OGM;

/**
 * @OGM\Node(label="Init")
 *
 * Class Init
 * @package GraphAware\Neo4j\OGM\Tests\Proxy
 */
class Init
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
    protected $name;

    /**
     * @OGM\Relationship(type="RELATES", direction="OUTGOING", targetEntity="Related", mappedBy="init")
     *
     * @var Related
     */
    protected $relation;

    /**
     * @var Profile
     *
     * @OGM\Relationship(type="HAS_PROFILE", direction="OUTGOING", targetEntity="Profile", mappedBy="init")
     * @OGM\Fetch()
     */
    protected $profile;

    public function __construct($name = null)
    {
        $this->name = $name;
    }

    /**
     * @return mixed
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
     * @return mixed
     */
    public function getRelation()
    {
        return $this->relation;
    }

    /**
     * @return Profile
     */
    public function getProfile()
    {
        return $this->profile;
    }

}