<?php

namespace GraphAware\Neo4j\OGM\Tests\Integration\Models\MoviesDemo;

use GraphAware\Neo4j\OGM\Annotations as OGM;
use GraphAware\Neo4j\OGM\Common\Collection;

/**
 * Class Person.
 *
 * @package GraphAware\Neo4j\OGM\Tests\Integration\Models\MoviesDemo
 *
 * @OGM\Node(label="Person")
 */
class Person
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
    protected $name;

    /**
     * @OGM\Property()
     *
     * @var int
     */
    protected $born;

    /**
     * @OGM\Relationship(type="ACTED_IN", direction="OUTGOING", targetEntity="Movie", mappedBy="actors", collection=true)
     *
     * @var Movie[]|Collection
     */
    protected $movies;

    public function __construct($name, $born = null)
    {
        $this->name = $name;
        $this->born = $born;
        $this->movies = new Collection();
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
     * @return int
     */
    public function getBorn()
    {
        return $this->born;
    }

    /**
     * @return Collection|Movie[]
     */
    public function getMovies()
    {
        return $this->movies;
    }

    /**
     * @param string $name
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    /**
     * @param int $born
     */
    public function setBorn($born)
    {
        $this->born = $born;
    }
}
