<?php

/*
 * This file is part of the GraphAware Neo4j PHP OGM package.
 *
 * (c) GraphAware Ltd <info@graphaware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

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
