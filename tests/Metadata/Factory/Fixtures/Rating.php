<?php

namespace GraphAware\Neo4j\OGM\Tests\Metadata\Factory\Fixtures;

use GraphAware\Neo4j\OGM\Annotations as OGM;

/**
 * @OGM\RelationshipEntity(type="RATED")
 */
class Rating
{
    /**
     * @OGM\GraphId()
     *
     * @var int
     */
    private $id;

    /**
     * @OGM\StartNode(targetEntity="Person")
     *
     * @var Person
     */
    private $person;

    /**
     * @OGM\EndNode(targetEntity="Movie")
     *
     * @var Movie
     */
    private $movie;

    /**
     * @OGM\Property(type="float")
     *
     * @var float
     */
    private $score;
}
