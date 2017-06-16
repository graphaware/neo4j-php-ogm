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
     * @OGM\Property()
     * @OGM\Convert(type="datetime", options={"db_format"="long", "timezone"="UTC"})
     */
    private $created;

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }
}
