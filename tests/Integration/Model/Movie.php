<?php

/*
 * This file is part of the GraphAware Neo4j PHP OGM package.
 *
 * (c) GraphAware Ltd <info@graphaware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace GraphAware\Neo4j\OGM\Tests\Integration\Model;

use Doctrine\Common\Collections\ArrayCollection;
use GraphAware\Neo4j\OGM\Annotations as OGM;

/**
 * @OGM\Node(label="Movie")
 */
class Movie
{
    /**
     * @OGM\GraphId()
     */
    public $id;

    /**
     * @OGM\Property(type="string")
     */
    public $title;

    /**
     * @OGM\Label(name="Released")
     */
    protected $isReleased;

    /**
     * @OGM\Relationship(targetEntity="Person", type="ACTED_IN", direction="INCOMING", collection=true)
     */
    public $actors;

    /**
     * @OGM\Relationship(targetEntity="Person", type="PLAYED_IN", direction="INCOMING", collection=true, mappedBy="movies")
     * @OGM\OrderBy(property="name", order="ASC")
     */
    public $players;

    /**
     * @OGM\Relationship(relationshipEntity="ScoreRel", type="HAS_SCORE", direction="OUTGOING")
     */
    protected $score;

    public function __construct($title = null)
    {
        if (null !== $title) {
            $this->title = $title;
        }
        $this->actors = new ArrayCollection();
        $this->players = new ArrayCollection();
    }

    public function setScore(ScoreRel $scoreRel)
    {
        $this->score = $scoreRel;
    }

    public function setReleased()
    {
        $this->isReleased = true;
    }

    /**
     * @return ScoreRel
     */
    public function getScore()
    {
        return $this->score;
    }

    /**
     * @return mixed
     */
    public function getPlayers()
    {
        return $this->players;
    }

    /**
     * @return mixed
     */
    public function getActors()
    {
        return $this->actors;
    }
}
