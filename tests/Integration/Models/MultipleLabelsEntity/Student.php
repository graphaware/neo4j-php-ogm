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
 * Class Student.
 *
 * @OGM\Node(labels={"Student", "Person"})
 */
class Student extends Person
{
    /**
     * @OGM\GraphId
     */
    protected $id;

    /**
     * @var int
     *
     * @OGM\Property(type="int", nullable=false)
     */
    private $course;

    /**
     * Student constructor.
     *
     * @param $first
     * @param $last
     * @param $course
     */
    public function __construct($first, $last, $course)
    {
        parent::__construct($first, $last);

        $this->course = $course;
    }

    /**
     * @return string
     */
    public function getCourse()
    {
        return $this->course;
    }

    /**
     * @param $course
     *
     * @return $this
     */
    public function setCourse($course)
    {
        $this->course = $course;

        return $this;
    }
}
