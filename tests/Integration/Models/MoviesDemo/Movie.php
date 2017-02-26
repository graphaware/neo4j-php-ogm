<?php

namespace GraphAware\Neo4j\OGM\Tests\Integration\Models\MoviesDemo;

use GraphAware\Neo4j\OGM\Annotations as OGM;
use GraphAware\Neo4j\OGM\Common\Collection;

/**
 * Class Movie.
 *
 * @package GraphAware\Neo4j\OGM\Tests\Integration\Models\MoviesDemo
 *
 * @OGM\Node(label="Movie")
 */
class Movie
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
     * @var string
     */
    protected $title;

    /**
     * @OGM\Property()
     *
     * @var string
     */
    protected $tagline;

    /**
     * @OGM\Property()
     *
     * @var int
     */
    protected $released;

    /**
     * @OGM\Relationship(type="ACTED_IN", direction="INCOMING", collection=true, targetEntity="Person", mappedBy="movies")
     *
     * @var Collection|Person[]
     */
    protected $actors;

    public function __construct($title)
    {
        $this->title = $title;
        $this->actors = new Collection();
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
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * @return string
     */
    public function getTagline()
    {
        return $this->tagline;
    }

    /**
     * @return int
     */
    public function getReleased()
    {
        return $this->released;
    }

    /**
     * @return Collection|Person[]
     */
    public function getActors()
    {
        return $this->actors;
    }

    /**
     * @param string $title
     */
    public function setTitle($title)
    {
        $this->title = $title;
    }

    /**
     * @param string $tagline
     */
    public function setTagline($tagline)
    {
        $this->tagline = $tagline;
    }

    /**
     * @param int $released
     */
    public function setReleased($released)
    {
        $this->released = $released;
    }

    public function getActor($name)
    {
        foreach ($this->getActors() as $actor) {
            if ($actor->getName() === $name) {
                return $actor;
            }
        }

        return null;
    }
}
