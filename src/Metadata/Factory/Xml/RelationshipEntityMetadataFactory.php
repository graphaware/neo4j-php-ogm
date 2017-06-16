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

use GraphAware\Neo4j\OGM\Annotations\RelationshipEntity;
use GraphAware\Neo4j\OGM\Exception\MappingException;
use GraphAware\Neo4j\OGM\Metadata\RelationshipEntityMetadata;
use GraphAware\Neo4j\OGM\Util\ClassUtils;

class RelationshipEntityMetadataFactory
{
    private $propertyXmlMetadataFactory;
    private $idXmlMetadataFactory;

    public function __construct(
        PropertyXmlMetadataFactory $propertyXmlMetadataFactory,
        IdXmlMetadataFactory $idXmlMetadataFactory
    ) {
        $this->propertyXmlMetadataFactory = $propertyXmlMetadataFactory;
        $this->idXmlMetadataFactory = $idXmlMetadataFactory;
    }

    /**
     * @param \SimpleXMLElement $node
     * @param string            $className
     *
     * @return RelationshipEntityMetadata
     */
    public function buildRelationshipEntityMetadata(\SimpleXMLElement $node, $className)
    {
        if (!isset($node->{'start-node'}) || !isset($node->{'end-node'})) {
            throw new MappingException(
                sprintf('Class "%s" OGM XML configuration is missing "start-node" or "end-node" attributes', $className)
            );
        }

        $startNode = $node->{'start-node'};
        if (!isset($startNode['name']) || !isset($startNode['target-entity'])) {
            throw new MappingException(
                sprintf('Class "%s" OGM XML start-node configuration is missing mandatory attributes', $className)
            );
        }
        $startNodeKey = (string) $startNode['name'];
        $startNodeClass = ClassUtils::getFullClassName((string) $startNode['target-entity'], $className);

        $endNode = $node->{'end-node'};
        if (!isset($endNode['name']) || !isset($endNode['target-entity'])) {
            throw new MappingException(
                sprintf('Class "%s" OGM XML end-node configuration is missing mandatory attributes', $className)
            );
        }
        $endNodeKey = (string) $endNode['name'];
        $endNodeClass = ClassUtils::getFullClassName((string) $endNode['target-entity'], $className);

        $reflection = new \ReflectionClass($className);

        return new RelationshipEntityMetadata(
            $className,
            $reflection,
            $this->buildRelationshipMetadata($node, $className),
            $this->idXmlMetadataFactory->buildEntityIdMetadata($node, $className, $reflection),
            $startNodeClass,
            $startNodeKey,
            $endNodeClass,
            $endNodeKey,
            $this->propertyXmlMetadataFactory->buildPropertiesMetadata($node, $className, $reflection)
        );
    }

    /**
     * @param \SimpleXMLElement $node
     * @param string            $className
     *
     * @return RelationshipEntity
     */
    private function buildRelationshipMetadata(\SimpleXMLElement $node, $className)
    {
        if (!isset($node['type'])) {
            throw new MappingException(
                sprintf('Class "%s" OGM XML configuration is missing "type" attribute', $className)
            );
        }
        $entity = new RelationshipEntity();
        $entity->type = (string) $node['type'];

        if (isset($node['direction'])) {
            $entity->direction = (string) $node['direction'];
        }

        return $entity;
    }
}
