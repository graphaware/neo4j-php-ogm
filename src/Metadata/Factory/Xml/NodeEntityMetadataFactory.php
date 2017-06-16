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

use GraphAware\Neo4j\OGM\Exception\MappingException;
use GraphAware\Neo4j\OGM\Metadata\NodeAnnotationMetadata;
use GraphAware\Neo4j\OGM\Metadata\NodeEntityMetadata;

class NodeEntityMetadataFactory
{
    private $propertyXmlMetadataFactory;
    private $relationshipXmlMetadataFactory;
    private $idXmlMetadataFactory;

    public function __construct(
        PropertyXmlMetadataFactory $propertyXmlMetadataFactory,
        RelationshipXmlMetadataFactory $relationshipXmlMetadataFactory,
        IdXmlMetadataFactory $idXmlMetadataFactory
    ) {
        $this->propertyXmlMetadataFactory = $propertyXmlMetadataFactory;
        $this->relationshipXmlMetadataFactory = $relationshipXmlMetadataFactory;
        $this->idXmlMetadataFactory = $idXmlMetadataFactory;
    }

    /**
     * @param \SimpleXMLElement $node
     * @param string            $className
     *
     * @return NodeEntityMetadata
     */
    public function buildNodeEntityMetadata(\SimpleXMLElement $node, $className)
    {
        $reflection = new \ReflectionClass($className);

        return new NodeEntityMetadata(
            $className,
            $reflection,
            $this->buildNodeMetadata($node, $className),
            $this->idXmlMetadataFactory->buildEntityIdMetadata($node, $className, $reflection),
            $this->propertyXmlMetadataFactory->buildPropertiesMetadata($node, $className, $reflection),
            $this->relationshipXmlMetadataFactory->buildRelationshipsMetadata($node, $className, $reflection)
        );
    }

    /**
     * @param \SimpleXMLElement $node
     * @param string            $className
     *
     * @return NodeAnnotationMetadata
     */
    private function buildNodeMetadata(\SimpleXMLElement $node, $className)
    {
        if (!isset($node['label'])) {
            throw new MappingException(
                sprintf('Class "%s" OGM XML node configuration is missing "label" attribute', $className)
            );
        }

        return new NodeAnnotationMetadata(
            (string) $node['label'],
            isset($node['repository-class']) ? (string) $node['repository-class'] : null
        );
    }
}
