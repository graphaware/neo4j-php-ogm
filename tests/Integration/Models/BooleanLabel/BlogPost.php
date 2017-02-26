<?php

namespace GraphAware\Neo4j\OGM\Tests\Integration\Models\BooleanLabel;

use GraphAware\Neo4j\OGM\Annotations as OGM;

/**
 * Class BlogPost.
 *
 * @package GraphAware\Neo4j\OGM\Tests\Integration\Models\BooleanLabel
 *
 * @OGM\Node(label="BlogPost")
 */
class BlogPost
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
     * @OGM\Label(name="Published")
     *
     * @var bool
     */
    protected $published;

    public function __construct($title)
    {
        $this->title = $title;
    }

    /**
     * @return mixed
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return mixed
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * @param mixed $title
     */
    public function setTitle($title)
    {
        $this->title = $title;
    }

    /**
     * @return mixed
     */
    public function getPublished()
    {
        return $this->published;
    }

    /**
     * @param mixed $published
     */
    public function setPublished($published)
    {
        $this->published = $published;
    }
}
