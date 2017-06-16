<?php

/*
 * This file is part of the GraphAware Neo4j PHP OGM package.
 *
 * (c) GraphAware Ltd <info@graphaware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace GraphAware\Neo4j\OGM\Tests\Integration\Models\OrderedRelationships;

use GraphAware\Neo4j\OGM\Annotations as OGM;

/**
 * Class Click.
 *
 * @OGM\Node(label="Click")
 */
class Click
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
     * @var int
     */
    protected $time;

    public function __construct($time)
    {
        $this->time = $time;
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return int
     */
    public function getTime()
    {
        return $this->time;
    }
}
