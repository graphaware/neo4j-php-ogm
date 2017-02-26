<?php

namespace GraphAware\Neo4j\OGM\Tests\Integration\Models\SimpleRelationshipEntity;

use GraphAware\Neo4j\OGM\Annotations as OGM;

/**
 * Class Guest.
 *
 * @package GraphAware\Neo4j\OGM\Tests\Integration\Models\SimpleRelationshipEntity
 *
 * @OGM\Node(label="Guest")
 */
class Guest
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
     * @OGM\Relationship(relationshipEntity="Rating", type="RATED", direction="OUTGOING")
     *
     * @var Rating
     */
    protected $rating;

    public function __construct($name)
    {
        $this->name = $name;
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
     * @param string $name
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    /**
     * @return Rating
     */
    public function getRating()
    {
        return $this->rating;
    }

    /**
     * @param Rating $rating
     */
    public function setRating($rating)
    {
        $this->rating = $rating;
    }
}
