<?php

/*
 * This file is part of the GraphAware Neo4j PHP OGM package.
 *
 * (c) GraphAware Ltd <info@graphaware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace GraphAware\Neo4j\OGM\Tests\Integration\Models\MultipleLabelsEntity;

use GraphAware\Neo4j\OGM\Annotations as OGM;

/**
 * Class Person.
 *
 * @OGM\Node(labels={"Person"})
 */
class Person
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
     * @OGM\Property(type="string", nullable=false)
     */
    protected $first;

    /**
     * @var string
     *
     * @OGM\Property(type="string", nullable=false)
     */
    protected $last;

    /**
     * Person constructor.
     *
     * @param string $first
     * @param string $last
     */
    public function __construct($first, $last)
    {
        $this->first = $first;
        $this->last = $last;
    }

    /**
     * @return string
     */
    public function getFirst()
    {
        return $this->first;
    }

    /**
     * @param $first
     *
     * @return $this
     */
    public function setFirst($first)
    {
        $this->first = $first;

        return $this;
    }

    /**
     * @return string
     */
    public function getLast()
    {
        return $this->last;
    }

    /**
     * @param $last
     *
     * @return $this
     */
    public function setLast($last)
    {
        $this->last = $last;

        return $this;
    }
}
