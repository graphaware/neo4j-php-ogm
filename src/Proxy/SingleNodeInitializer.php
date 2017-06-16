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

use GraphAware\Common\Type\Node;
use GraphAware\Neo4j\OGM\EntityManager;
use GraphAware\Neo4j\OGM\Metadata\NodeEntityMetadata;
use GraphAware\Neo4j\OGM\Metadata\RelationshipMetadata;

class SingleNodeInitializer
{
    protected $em;

    protected $relationshipMetadata;

    protected $metadata;

    public function __construct(EntityManager $em, RelationshipMetadata $relationshipMetadata, NodeEntityMetadata $nodeEntityMetadata)
    {
        $this->em = $em;
        $this->relationshipMetadata = $relationshipMetadata;
        $this->metadata = $nodeEntityMetadata;
    }

    public function initialize(Node $node, $baseInstance)
    {
        $persister = $this->em->getEntityPersister($this->metadata->getClassName());
        $persister->getSimpleRelationship($this->relationshipMetadata->getPropertyName(), $baseInstance);
    }
}
