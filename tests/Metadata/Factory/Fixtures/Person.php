<?php

namespace GraphAware\Neo4j\OGM\Tests\Metadata\Factory\Fixtures;

use GraphAware\Neo4j\OGM\Annotations as OGM;

/**
 * @OGM\Node(label="Person")
 */
class Person
{
    /**
     * @OGM\GraphId()
     *
     * @var int
     */
    private $id;

    /**
     * @OGM\Property(type="string")
     *
     * @var string
     */
    private $name;

    /**
     * @OGM\Property(type="int",nullable=false)
     * @OGM\Label(name="my-age")
     *
     * @var int
     */
    private $age;

    /**
     * @OGM\Relationship(type="ACTED_IN", direction="OUTGOING", targetEntity="Movie", collection=true, mappedBy="actors")
     * @OGM\OrderBy(property="name", order="DESC")
     *
     * @var Movie[]
     */
    private $movies;

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }
}
