<?php

/*
 * This file is part of the GraphAware Neo4j PHP OGM package.
 *
 * (c) GraphAware Ltd <info@graphaware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace GraphAware\Neo4j\OGM\Metadata;

use GraphAware\Neo4j\OGM\Util\ClassUtils;

final class NodeEntityMetadata extends GraphEntityMetadata
{
    /**
     * @var LabeledPropertyMetadata[]
     */
    protected $labeledPropertiesMetadata = [];

    /**
     * @var RelationshipMetadata[]
     */
    protected $relationships = [];
    /**
     * @var \GraphAware\Neo4j\OGM\Metadata\NodeAnnotationMetadata
     */
    private $nodeAnnotationMetadata;

    /**
     * @var string
     */
    private $customRepository;

    /**
     * NodeEntityMetadata constructor.
     *
     * @param string                                                $className
     * @param \ReflectionClass                                      $reflectionClass
     * @param \GraphAware\Neo4j\OGM\Metadata\NodeAnnotationMetadata $nodeAnnotationMetadata
     * @param \GraphAware\Neo4j\OGM\Metadata\EntityIdMetadata       $entityIdMetadata
     * @param array                                                 $entityPropertiesMetadata
     * @param RelationshipMetadata[]                                $simpleRelationshipsMetadata
     */
    public function __construct(
        $className,
        \ReflectionClass $reflectionClass,
        NodeAnnotationMetadata $nodeAnnotationMetadata,
        EntityIdMetadata $entityIdMetadata,
        array $entityPropertiesMetadata,
        array $simpleRelationshipsMetadata
    ) {
        parent::__construct($entityIdMetadata, $className, $reflectionClass, $entityPropertiesMetadata);
        $this->nodeAnnotationMetadata = $nodeAnnotationMetadata;
        $this->customRepository = $this->nodeAnnotationMetadata->getCustomRepository();
        foreach ($entityPropertiesMetadata as $o) {
            if ($o instanceof LabeledPropertyMetadata) {
                $this->labeledPropertiesMetadata[$o->getPropertyName()] = $o;
            }
        }
        foreach ($simpleRelationshipsMetadata as $relationshipMetadata) {
            $this->relationships[$relationshipMetadata->getPropertyName()] = $relationshipMetadata;
        }
    }

    /**
     * @return string
     */
    public function getLabel()
    {
        return $this->nodeAnnotationMetadata->getLabel();
    }

    /**
     * @param $key
     *
     * @return \GraphAware\Neo4j\OGM\Metadata\LabeledPropertyMetadata
     */
    public function getLabeledProperty($key)
    {
        if (array_key_exists($key, $this->labeledPropertiesMetadata)) {
            return $this->labeledPropertiesMetadata[$key];
        }
    }

    /**
     * @return \GraphAware\Neo4j\OGM\Metadata\LabeledPropertyMetadata[]
     */
    public function getLabeledProperties()
    {
        return $this->labeledPropertiesMetadata;
    }

    /**
     * @param $object
     *
     * @return LabeledPropertyMetadata[]
     */
    public function getLabeledPropertiesToBeSet($object)
    {
        return array_filter($this->getLabeledProperties(), function (LabeledPropertyMetadata $labeledPropertyMetadata) use ($object) {
            return true === $labeledPropertyMetadata->getValue($object);
        });
    }

    /**
     * @return bool
     */
    public function hasCustomRepository()
    {
        return null !== $this->customRepository;
    }

    /**
     * @return string
     */
    public function getRepositoryClass()
    {
        if (null === $this->customRepository) {
            throw new \LogicException(sprintf('There is no custom repository for "%s"', $this->className));
        }

        return ClassUtils::getFullClassName($this->customRepository, $this->className);
    }

    /**
     * @return \GraphAware\Neo4j\OGM\Metadata\RelationshipMetadata[]
     */
    public function getRelationships()
    {
        return $this->relationships;
    }

    /**
     * Returns non-lazy relationships.
     * Note that currently relationships that are not of type "collection" are considered non-lazy.
     *
     * @return RelationshipMetadata[]
     */
    public function getNonLazyRelationships()
    {
        $rels = [];
        foreach ($this->relationships as $relationship) {
            if (!$relationship->isLazy()) {
                $rels[] = $relationship;
            }
        }

        return $rels;
    }

    /**
     * @param mixed $andRelEntities
     *
     * @return RelationshipMetadata[]
     */
    public function getLazyRelationships($andRelEntities = false)
    {
        $rels = [];
        foreach ($this->relationships as $relationship) {
            if ($relationship->isLazy()) {
                if ($relationship->isRelationshipEntity() && !$andRelEntities) {
                    continue;
                }
                $rels[] = $relationship;
            }
        }

        return $rels;
    }

    /**
     * @param bool $andRelationshipEntities
     *
     * @return RelationshipMetadata[]
     */
    public function getFetchRelationships($andRelationshipEntities = false)
    {
        $rels = [];
        foreach ($this->relationships as $relationship) {
            if ($relationship->isFetch() && !$relationship->isRelationshipEntity()) {
                $rels[] = $relationship;
            }
        }

        return $rels;
    }

    /**
     * @param $key
     *
     * @return \GraphAware\Neo4j\OGM\Metadata\RelationshipMetadata
     */
    public function getRelationship($key)
    {
        if (array_key_exists($key, $this->relationships)) {
            return $this->relationships[$key];
        }

        return null;
    }

    /**
     * @param mixed $andLazy
     *
     * @return RelationshipMetadata[]
     */
    public function getSimpleRelationships($andLazy = true)
    {
        $coll = [];
        foreach ($this->relationships as $relationship) {
            if (!$relationship->isRelationshipEntity() && (!$relationship->isLazy() || $relationship->isLazy() === $andLazy)) {
                $coll[] = $relationship;
            }
        }

        return $coll;
    }

    /**
     * @return RelationshipMetadata[]|RelationshipEntityMetadata[]
     */
    public function getRelationshipEntities()
    {
        $coll = [];
        foreach ($this->relationships as $relationship) {
            if ($relationship->isRelationshipEntity()) {
                $coll[] = $relationship;
            }
        }

        return $coll;
    }

    /**
     * @return array
     */
    public function getAssociatedObjects()
    {
        return $this->getSimpleRelationships();
    }

    public function hasAssociation($fieldName)
    {
        foreach ($this->relationships as $relationship) {
            if ($relationship->getPropertyName() === $fieldName) {
                return true;
            }
        }

        return false;
    }

    public function isSingleValuedAssociation($fieldName)
    {
        foreach ($this->relationships as $relationship) {
            if ($relationship->getPropertyName() === $fieldName && !$relationship->isCollection()) {
                return true;
            }
        }

        return false;
    }

    public function isCollectionValuedAssociation($fieldName)
    {
        foreach ($this->relationships as $relationship) {
            if ($relationship->getPropertyName() === $fieldName && $relationship->isCollection()) {
                return true;
            }
        }

        return false;
    }

    public function getAssociationNames()
    {
        $names = [];
        foreach ($this->relationships as $relationship) {
            $names[] = $relationship->getPropertyName();
        }

        return $names;
    }

    public function getAssociationTargetClass($assocName)
    {
        foreach ($this->relationships as $relationship) {
            if ($relationship->getPropertyName() === $assocName) {
                if ($relationship->isRelationshipEntity()) {
                    return $relationship->getRelationshipEntityClass();
                }

                return $relationship->getTargetEntity();
            }
        }

        return null;
    }

    public function isAssociationInverseSide($assocName)
    {
        // is not implemented in the context of the ogm.
        // if entities should be hydrated on the inversed entity, the only mappedBy annotation property should be used.

        return false;
    }

    public function getAssociationMappedByTargetField($assocName)
    {
        foreach ($this->relationships as $relationship) {
            if ($relationship->hasMappedByProperty() && $relationship->getMappedByProperty() === $assocName) {
                return $relationship->getPropertyName();
            }
        }

        return null;
    }

    public function getMappedByFieldsForFetch()
    {
        $fields = [];
        foreach ($this->getFetchRelationships() as $relationship) {
            if ($relationship->hasMappedByProperty()) {
                $fields[] = $relationship->getMappedByProperty();
            }
        }

        return $fields;
    }
}
