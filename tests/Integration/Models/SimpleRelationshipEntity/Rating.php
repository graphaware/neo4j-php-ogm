<?php

namespace GraphAware\Neo4j\OGM\Tests\Integration\Models\SimpleRelationshipEntity;

use GraphAware\Neo4j\OGM\Annotations as OGM;

/**
 * Class Rating.
 *
 * @package GraphAware\Neo4j\OGM\Tests\Integration\Models\SimpleRelationshipEntity
 *
 * @OGM\RelationshipEntity(type="RATED")
 */
class Rating
{
    /**
     * @OGM\GraphId()
     *
     * @var int
     */
    protected $id;

    /**
     * @OGM\StartNode(targetEntity="Guest")
     *
     * @var Guest
     */
    protected $guest;

    /**
     * @OGM\EndNode(targetEntity="Hotel")
     *
     * @var Hotel
     */
    protected $hotel;

    /**
     * @OGM\Property()
     *
     * @var float
     */
    protected $score;

    /**
     * @param Guest $guest
     * @param Hotel $hotel
     * @param float $score
     */
    public function __construct(Guest $guest, Hotel $hotel, $score)
    {
        $this->guest = $guest;
        $this->hotel = $hotel;
        $this->score = $score;
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return Guest
     */
    public function getGuest()
    {
        return $this->guest;
    }

    /**
     * @param Guest $guest
     */
    public function setGuest($guest)
    {
        $this->guest = $guest;
    }

    /**
     * @return Hotel
     */
    public function getHotel()
    {
        return $this->hotel;
    }

    /**
     * @param Hotel $hotel
     */
    public function setHotel($hotel)
    {
        $this->hotel = $hotel;
    }

    /**
     * @return float
     */
    public function getScore()
    {
        return $this->score;
    }

    /**
     * @param float $score
     */
    public function setScore($score)
    {
        $this->score = $score;
    }
}
