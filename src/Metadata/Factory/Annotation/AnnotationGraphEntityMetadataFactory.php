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
use GraphAware\Neo4j\OGM\Annotations\Fetch;
use GraphAware\Neo4j\OGM\Annotations\Label;
use GraphAware\Neo4j\OGM\Annotations\Lazy;
use GraphAware\Neo4j\OGM\Annotations\MappedResult;
use GraphAware\Neo4j\OGM\Annotations\Node;
use GraphAware\Neo4j\OGM\Annotations\OrderBy;
use GraphAware\Neo4j\OGM\Annotations\QueryResult;
use GraphAware\Neo4j\OGM\Annotations\Relationship;
use GraphAware\Neo4j\OGM\Annotations\RelationshipEntity;
use GraphAware\Neo4j\OGM\Exception\MappingException;
use GraphAware\Neo4j\OGM\Metadata\EntityIdMetadata;
use GraphAware\Neo4j\OGM\Metadata\EntityPropertyMetadata;
use GraphAware\Neo4j\OGM\Metadata\Factory\GraphEntityMetadataFactoryInterface;
use GraphAware\Neo4j\OGM\Metadata\LabeledPropertyMetadata;
use GraphAware\Neo4j\OGM\Metadata\NodeEntityMetadata;
use GraphAware\Neo4j\OGM\Metadata\QueryResultMapper;
use GraphAware\Neo4j\OGM\Metadata\RelationshipMetadata;
use GraphAware\Neo4j\OGM\Metadata\ResultField;

class AnnotationGraphEntityMetadataFactory implements GraphEntityMetadataFactoryInterface
{
    /**
     * @var Reader
     */
    private $reader;

    /**
     * @var NodeAnnotationMetadataFactory
     */
    private $nodeAnnotationMetadataFactory;

    /**
     * @var PropertyAnnotationMetadataFactory
     */
    private $propertyAnnotationMetadataFactory;

    /**
     * @var IdAnnotationMetadataFactory
     */
    private $IdAnnotationMetadataFactory;

    /**
     * @var RelationshipEntityMetadataFactory
     */
    private $relationshipEntityMetadataFactory;

    /**
     * @param Reader $reader
     */
    public function __construct(Reader $reader)
    {
        $this->reader = $reader;
        $this->nodeAnnotationMetadataFactory = new NodeAnnotationMetadataFactory($reader);
        $this->propertyAnnotationMetadataFactory = new PropertyAnnotationMetadataFactory($reader);
        $this->IdAnnotationMetadataFactory = new IdAnnotationMetadataFactory($reader);
        $this->relationshipEntityMetadataFactory = new RelationshipEntityMetadataFactory($reader);
    }

    public function create($className)
    {
        $reflectionClass = new \ReflectionClass($className);
        $entityIdMetadata = null;
        $propertiesMetadata = [];
        $relationshipsMetadata = [];

        if (null !== $annotation = $this->reader->getClassAnnotation($reflectionClass, Node::class)) {
            $annotationMetadata = $this->nodeAnnotationMetadataFactory->create($className);
            foreach ($reflectionClass->getProperties() as $reflectionProperty) {
                $propertyAnnotationMetadata = $this->propertyAnnotationMetadataFactory->create($className, $reflectionProperty->getName());
                $converter = $this->reader->getPropertyAnnotation($reflectionProperty, Convert::class);
                if (null !== $propertyAnnotationMetadata) {
                    $propertiesMetadata[] = new EntityPropertyMetadata($reflectionProperty->getName(), $reflectionProperty, $propertyAnnotationMetadata, $converter);
                } else {
                    $idA = $this->IdAnnotationMetadataFactory->create($className, $reflectionProperty);
                    if (null !== $idA) {
                        $entityIdMetadata = new EntityIdMetadata($reflectionProperty->getName(), $reflectionProperty, $idA);
                    }
                }
                foreach ($this->reader->getPropertyAnnotations($reflectionProperty) as $annot) {
                    if ($annot instanceof Label) {
                        $propertiesMetadata[] = new LabeledPropertyMetadata($reflectionProperty->getName(), $reflectionProperty, $annot);
                    }

                    if ($annot instanceof Relationship) {
                        $isLazy = null !== $this->reader->getPropertyAnnotation($reflectionProperty, Lazy::class);
                        $isFetch = null !== $this->reader->getPropertyAnnotation($reflectionProperty, Fetch::class);
                        $orderBy = $this->reader->getPropertyAnnotation($reflectionProperty, OrderBy::class);
                        $relationshipsMetadata[] = new RelationshipMetadata($className, $reflectionProperty, $annot, $isLazy, $isFetch, $orderBy);
                    }
                }
            }

            if ($entityIdMetadata === null) {
                throw new MappingException(sprintf('The class "%s" must have ID mapping defined', $className));
            }

            return new NodeEntityMetadata($className, $reflectionClass, $annotationMetadata, $entityIdMetadata, $propertiesMetadata, $relationshipsMetadata);
        } elseif (null !== $annotation = $this->reader->getClassAnnotation($reflectionClass, RelationshipEntity::class)) {
            return $this->relationshipEntityMetadataFactory->create($className);
        }

        if (false !== get_parent_class($className)) {
            return $this->create(get_parent_class($className));
        }

        throw new MappingException(sprintf('The class "%s" is not a valid OGM entity', $className));
    }

    public function supports($className)
    {
        $reflectionClass = new \ReflectionClass($className);

        if (
            $this->reader->getClassAnnotation($reflectionClass, Node::class) === null
            && get_parent_class($className) !== false
        ) {
            return $this->supports(get_parent_class($className));
        }

        return true;
    }

    public function supportsQueryResult($className)
    {
        $reflClass = new \ReflectionClass($className);
        $classAnnotations = $this->reader->getClassAnnotations($reflClass);

        foreach ($classAnnotations as $classAnnotation) {
            if ($classAnnotation instanceof QueryResult) {
                return true;
            }
        }

        return false;
    }

    public function createQueryResultMapper($className)
    {
        $reflClass = new \ReflectionClass($className);
        $queryResultMapper = new QueryResultMapper($className);

        foreach ($reflClass->getProperties() as $property) {
            foreach ($this->reader->getPropertyAnnotations($property) as $propertyAnnotation) {
                if ($propertyAnnotation instanceof MappedResult) {
                    $queryResultMapper->addField(new ResultField($property->getName(), $propertyAnnotation->type, $propertyAnnotation->target));
                }
            }
        }

        return $queryResultMapper;
    }
}
