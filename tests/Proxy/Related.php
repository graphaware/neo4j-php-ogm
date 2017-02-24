<?php

namespace GraphAware\Neo4j\OGM\Tests\Proxy;

use GraphAware\Neo4j\OGM\Annotations as OGM;

/**
 *
 * @OGM\Node(label="Related")
 *
 * Class Related
 * @package GraphAware\Neo4j\OGM\Tests\Proxy
 *
 */
class Related
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
     * @var Init
     *
     * @OGM\Relationship(type="RELATES", targetEntity="Init", direction="INCOMING")
     */
    protected $init;

    public function __construct($name = null)
    {
        $this->name = $name;
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
     * @return Init
     */
    public function getInit()
    {
        return $this->init;
    }
}