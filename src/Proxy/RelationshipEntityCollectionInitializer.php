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
use GraphAware\Neo4j\OGM\Metadata\RelationshipMetadata;

class RelationshipEntityCollectionInitializer extends RelationshipEntityInitializer
{
    public function initialize(Node $node, $baseInstance)
    {
        $persister = $this->em->getEntityPersister($this->metadata->getClassName());
        $persister->getRelationshipEntityCollection($this->relationshipMetadata->getPropertyName(), $baseInstance);
    }

    public function getCount($baseInstance, RelationshipMetadata $relationshipMetadata)
    {
        $persister = $this->em->getEntityPersister($this->metadata->getClassName());

        return $persister->getCountForRelationship($relationshipMetadata->getPropertyName(), $baseInstance);
    }
}
