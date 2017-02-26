<?php

namespace GraphAware\Neo4j\OGM\Tests\Metadata\Factory\Fixtures;

use GraphAware\Neo4j\OGM\Annotations as OGM;

/**
 * @OGM\Node(label="Movie",repository="GraphAware\Neo4j\OGM\Tests\Metadata\Factory\Fixtures\MovieRepository")
 */
class Movie
{
    /**
     * @OGM\GraphId()
     */
    private $id;

    /**
     * @OGM\Property(type="string",nullable=true)
     *
     * @var string
     */
    private $name;

    /**
     * @OGM\Relationship(type="ACTED_IN", direction="OUTGOING", targetEntity="Person", collection=true)
     *
     * @var Person[]
     */
    private $actors;
}
