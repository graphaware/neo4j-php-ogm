<?php

/*
 * This file is part of the GraphAware Neo4j PHP OGM package.
 *
 * (c) GraphAware Ltd <info@graphaware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace GraphAware\Neo4j\OGM\Metadata\Factory\Annotation;

use Doctrine\Common\Annotations\Reader;
use GraphAware\Neo4j\OGM\Annotations\Convert;
use GraphAware\Neo4j\OGM\Annotations\EndNode;
use GraphAware\Neo4j\OGM\Annotations\GraphId;
use GraphAware\Neo4j\OGM\Annotations\Property;
use GraphAware\Neo4j\OGM\Annotations\RelationshipEntity;
use GraphAware\Neo4j\OGM\Annotations\StartNode;
use GraphAware\Neo4j\OGM\Metadata\EntityIdMetadata;
use GraphAware\Neo4j\OGM\Metadata\EntityPropertyMetadata;
use GraphAware\Neo4j\OGM\Metadata\IdAnnotationMetadata;
use GraphAware\Neo4j\OGM\Metadata\RelationshipEntityMetadata;
use GraphAware\Neo4j\OGM\Util\ClassUtils;

final class RelationshipEntityMetadataFactory
{
    private $reader;

    private $propertyAnnotationMetadataFactory;

    public function __construct(Reader $reader)
    {
        $this->reader = $reader;
        $this->propertyAnnotationMetadataFactory = new PropertyAnnotationMetadataFactory($reader);
    }

    public function create($class)
    {
        $reflectionClass = new \ReflectionClass($class);
        $annotation = $this->reader->getClassAnnotation($reflectionClass, RelationshipEntity::class);
        $entityIdMetadata = null;
        $startNodeMetadata = null;
        $endNodeMetadata = null;
        $propertiesMetadata = [];
        $startNodeKey = null;
        $endNodeKey = null;

        foreach ($reflectionClass->getProperties() as $reflectionProperty) {
            if (null === $entityIdMetadata && null !== $idAnnotation = $this->reader->getPropertyAnnotation($reflectionProperty, GraphId::class)) {
                $entityIdMetadata = new EntityIdMetadata($reflectionProperty->getName(), $reflectionProperty, new IdAnnotationMetadata());
                continue;
            }

            if (null === $startNodeMetadata && null !== $startAnnotation = $this->reader->getPropertyAnnotation($reflectionProperty, StartNode::class)) {
                $startNodeClass = ClassUtils::getFullClassName($startAnnotation->targetEntity, $class);
                $startNodeMetadata = $startNodeClass;
                $startNodeKey = $reflectionProperty->getName();
                continue;
            }

            if (null === $endNodeMetadata && null !== $endAnnotation = $this->reader->getPropertyAnnotation($reflectionProperty, EndNode::class)) {
                $endNodeClass = ClassUtils::getFullClassName($endAnnotation->targetEntity, $class);
                $endNodeMetadata = $endNodeClass;
                $endNodeKey = $reflectionProperty->getName();
                continue;
            }

            $converter = $this->reader->getPropertyAnnotation($reflectionProperty, Convert::class);

            if (null !== $propertyAnnotation = $this->reader->getPropertyAnnotation($reflectionProperty, Property::class)) {
                $propertiesMetadata[] = new EntityPropertyMetadata($reflectionProperty->getName(), $reflectionProperty, $this->propertyAnnotationMetadataFactory->create($class, $reflectionProperty->getName()), $converter);
            }
        }

        return new RelationshipEntityMetadata($class, $reflectionClass, $annotation, $entityIdMetadata, $startNodeMetadata, $startNodeKey, $endNodeMetadata, $endNodeKey, $propertiesMetadata);
    }
}
