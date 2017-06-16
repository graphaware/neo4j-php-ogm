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

use GraphAware\Neo4j\OGM\Annotations\Label;
use GraphAware\Neo4j\OGM\Exception\MappingException;
use GraphAware\Neo4j\OGM\Metadata\EntityPropertyMetadata;
use GraphAware\Neo4j\OGM\Metadata\LabeledPropertyMetadata;
use GraphAware\Neo4j\OGM\Metadata\PropertyAnnotationMetadata;

class PropertyXmlMetadataFactory
{
    /**
     * @param \SimpleXMLElement $node
     * @param string            $className
     * @param \ReflectionClass  $reflection
     *
     * @return array
     */
    public function buildPropertiesMetadata(\SimpleXMLElement $node, $className, \ReflectionClass $reflection)
    {
        $properties = [];
        foreach ($node->property as $propertyNode) {
            $properties[] = $this->buildPropertyMetadata($propertyNode, $className, $reflection);
            if (isset($propertyNode->label)) {
                $properties[] = $this->buildLabeledPropertyMetadata($propertyNode, $className, $reflection);
            }
        }

        return $properties;
    }

    /**
     * @param \SimpleXMLElement $propertyNode
     * @param string            $className
     * @param \ReflectionClass  $reflection
     *
     * @return EntityPropertyMetadata
     */
    private function buildPropertyMetadata(\SimpleXMLElement $propertyNode, $className, \ReflectionClass $reflection)
    {
        if (!isset($propertyNode['name']) || !isset($propertyNode['type'])) {
            throw new MappingException(
                sprintf('Class "%s" OGM XML property configuration is missing "name" or "type" attribute', $className)
            );
        }
        $name = (string) $propertyNode['name'];
        $nullable = true;
        if (isset($propertyNode['nullable'])) {
            if ((string) $propertyNode['nullable'] === 'true') {
                $nullable = true;
            }
            if ((string) $propertyNode['nullable'] === 'false') {
                $nullable = false;
            }
        }

        return new EntityPropertyMetadata(
            $name,
            $reflection->getProperty($name),
            new PropertyAnnotationMetadata(
                (string) $propertyNode['type'],
                isset($propertyNode['key']) ? (string) $propertyNode['key'] : null,
                $nullable
            )
        );
    }

    /**
     * @param \SimpleXMLElement $propertyNode
     * @param string            $className
     * @param \ReflectionClass  $reflection
     *
     * @return LabeledPropertyMetadata
     */
    private function buildLabeledPropertyMetadata(
        \SimpleXMLElement $propertyNode,
        $className,
        \ReflectionClass $reflection
    ) {
        if (!isset($propertyNode->label['name'])) {
            throw new MappingException(
                sprintf('Class "%s" OGM XML property label configuration is missing "name" attribute', $className)
            );
        }

        $name = (string) $propertyNode['name'];
        $label = new Label();
        $label->name = (string) $propertyNode->label['name'];

        return new LabeledPropertyMetadata(
            $name,
            $reflection->getProperty($name),
            $label
        );
    }
}
