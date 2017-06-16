<?php

/*
 * This file is part of the GraphAware Neo4j PHP OGM package.
 *
 * (c) GraphAware Ltd <info@graphaware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace GraphAware\Neo4j\OGM\Tests\Integration\Models\SimpleRelationshipEntity;

use GraphAware\Neo4j\OGM\Annotations as OGM;

/**
 * Class Rating.
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
     * @var \DateTime
     *
     * @OGM\Property()
     * @OGM\Convert(type="datetime", options={"format":"long_timestamp"})
     */
    protected $created;

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
        $this->created = new \DateTime("NOW");
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

    /**
     * @return \DateTime
     */
    public function getCreated()
    {
        return $this->created;
    }

    /**
     * @param \DateTime $created
     */
    public function setCreated($created)
    {
        $this->created = $created;
    }
}
