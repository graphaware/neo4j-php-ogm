<?php

/*
 * This file is part of the GraphAware Neo4j PHP OGM package.
 *
 * (c) GraphAware Ltd <info@graphaware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace GraphAware\Neo4j\OGM\Proxy;

use Doctrine\Common\Collections\AbstractLazyCollection;
use GraphAware\Common\Type\Node;
use GraphAware\Neo4j\OGM\Common\Collection;
use GraphAware\Neo4j\OGM\Metadata\RelationshipMetadata;

class LazyCollection extends AbstractLazyCollection
{
    private $initalizer;

    private $node;

    private $object;

    private $initializing = false;

    private $added = [];

    private $countTriggered = false;

    private $initialCount;

    private $relationshipMetadata;

    public function __construct(SingleNodeInitializer $initializer, Node $node, $object, RelationshipMetadata $relationshipMetadata)
    {
        $this->initalizer = $initializer;
        $this->node = $node;
        $this->object = $object;
        $this->collection = new Collection();
        $this->relationshipMetadata = $relationshipMetadata;
    }

    protected function doInitialize()
    {
        if ($this->initialized || $this->initializing) {
            return;
        }
        $this->initializing = true;
        $this->initalizer->initialize($this->node, $this->object);
        $this->initialized = true;
        $this->initializing = false;
        $this->collection = new Collection($this->added);
    }

    public function add($element, $andFetch = true)
    {
        $this->added[] = $element;
        if (!$andFetch) {
            return true;
        }
        return parent::add($element);
    }

    public function getAddWithoutFetch()
    {
        return $this->added;
    }

    public function removeElement($element)
    {
        if (in_array($element, $this->added)) {
            unset($this->added[array_search($element, $this->added)]);
        }
        return parent::removeElement($element);
    }

    public function count()
    {
        if ($this->initialized) {
            return parent::count();
        }

        if (!$this->countTriggered) {
            $this->initialCount = $this->initalizer->getCount($this->object, $this->relationshipMetadata);
            $this->countTriggered = true;
        }

        return $this->initialCount + count($this->collection);
    }


}