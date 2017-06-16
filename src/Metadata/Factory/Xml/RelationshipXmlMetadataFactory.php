<?php

/*
 * This file is part of the GraphAware Neo4j PHP OGM package.
 *
 * (c) GraphAware Ltd <info@graphaware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace GraphAware\Neo4j\OGM\Metadata\Factory\Xml;

use GraphAware\Neo4j\OGM\Annotations\OrderBy;
use GraphAware\Neo4j\OGM\Annotations\Relationship;
use GraphAware\Neo4j\OGM\Exception\MappingException;
use GraphAware\Neo4j\OGM\Metadata\RelationshipMetadata;

class RelationshipXmlMetadataFactory
{
    /**
     * @param \SimpleXMLElement $node
     * @param string            $className
     * @param \ReflectionClass  $reflection
     *
     * @return array
     */
    public function buildRelationshipsMetadata(\SimpleXMLElement $node, $className, \ReflectionClass $reflection)
    {
        $relationships = [];
        foreach ($node->relationship as $relationshipNode) {
            $relationships[] = $this->buildRelationshipMetadata($relationshipNode, $className, $reflection);
        }

        return $relationships;
    }

    /**
     * @param \SimpleXMLElement $relationshipNode
     * @param string            $className
     * @param \ReflectionClass  $reflection
     *
     * @return RelationshipMetadata
     */
    private function buildRelationshipMetadata(
        \SimpleXMLElement $relationshipNode,
        $className,
        \ReflectionClass $reflection
    ) {
        if (
            !isset($relationshipNode['name'])
            || !isset($relationshipNode['type'])
            || !isset($relationshipNode['direction'])
            || !isset($relationshipNode['target-entity'])
        ) {
            throw new MappingException(sprintf(
                'Class "%s" OGM XML property relationship configuration is missing mandatory attributes',
                $className
            ));
        }
        $relationship = new Relationship();

        $relationship->type = (string) $relationshipNode['type'];
        $relationship->direction = (string) $relationshipNode['direction'];
        $relationship->targetEntity = (string) $relationshipNode['target-entity'];

        $relationship->relationshipEntity = isset($relationshipNode['relationship-entity'])
            ? (string) $relationshipNode['relationship-entity']
            : null
        ;
        $relationship->mappedBy = isset($relationshipNode['mapped-by'])
            ? (string) $relationshipNode['mapped-by']
            : null
        ;
        if (isset($relationshipNode['collection'])) {
            if ((string) $relationshipNode['collection'] === 'true') {
                $relationship->collection = true;
            }
            if ((string) $relationshipNode['collection'] === 'false') {
                $relationship->collection = false;
            }
        }

        $orderBy = null;
        if (isset($relationshipNode->{'order-by'})) {
            $orderNode = $relationshipNode->{'order-by'};
            if (!isset($orderNode['property']) || !isset($orderNode['order'])) {
                throw new MappingException(sprintf(
                    'Class "%s" OGM XML property relationship order configuration is missing mandatory attributes',
                    $className
                ));
            }
            $orderBy = new OrderBy();
            $orderBy->order = (string) $orderNode['order'];
            $orderBy->property = (string) $orderNode['property'];
        }

        return new RelationshipMetadata(
            $className,
            $reflection->getProperty((string) $relationshipNode['name']),
            $relationship,
            isset($relationshipNode->lazy),
            $orderBy
        );
    }
}
