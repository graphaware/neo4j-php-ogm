<?php

namespace GraphAware\Neo4j\OGM\Tests\Util;

use GraphAware\Common\Type\Node;

class NodeProxy implements Node
{
    protected $id;

    public function __construct($id = null)
    {
        $this->id = $id;
    }

    public function identity()
    {
        return $this->id;
    }

    public function keys()
    {
        // TODO: Implement keys() method.
    }

    public function containsKey($key)
    {
        // TODO: Implement containsKey() method.
    }

    public function get($key)
    {
        // TODO: Implement get() method.
    }

    public function __get($name)
    {
        // TODO: Implement __get() method.
    }

    public function hasValue($key)
    {
        // TODO: Implement hasValue() method.
    }

    public function value($key, $default = null)
    {
        // TODO: Implement value() method.
    }

    public function values()
    {
        // TODO: Implement values() method.
    }

    public function asArray()
    {
        // TODO: Implement asArray() method.
    }

    public function labels()
    {
        // TODO: Implement labels() method.
    }

    public function hasLabel($label)
    {
        // TODO: Implement hasLabel() method.
    }

}