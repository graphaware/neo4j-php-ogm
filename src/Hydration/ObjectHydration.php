<?php


/*
 * This file is part of the GraphAware Neo4j PHP OGM package.
 *
 * (c) GraphAware Ltd <info@graphaware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace GraphAware\Neo4j\OGM\Hydration;

use GraphAware\Common\Result\Record;
use GraphAware\Common\Result\Result;
use GraphAware\Common\Type\Node;
use GraphAware\Neo4j\OGM\Annotations\Label;
use GraphAware\Neo4j\OGM\EntityManager;
use GraphAware\Neo4j\OGM\Finder\RelationshipsFinder;
use GraphAware\Neo4j\OGM\Lazy\LazyRelationshipCollection;
use GraphAware\Neo4j\OGM\Metadata\EntityPropertyMetadata;
use GraphAware\Neo4j\OGM\Metadata\NodeEntityMetadata;
use GraphAware\Neo4j\OGM\Metadata\QueryResultMapper;
use GraphAware\Neo4j\OGM\Metadata\RelationshipEntityMetadata;
use GraphAware\Neo4j\OGM\Metadata\RelationshipMetadata;
use GraphAware\Neo4j\OGM\Util\ClassUtils;
use GraphAware\Neo4j\OGM\Util\ProxyUtils;
use ProxyManager\Version;


/**
 * A hydrator is a class that provides some form
 * of transformation of an SQL result set into another structure.
 *
 * @author Christophe Willemsen <christophe@graphaware.com>
 * @author Tobias Nyholm <tobias.nyholm@gmail.com>
 */
class ObjectHydration
{
    const OPTION_ADD_LAZY_LOAD = 'lazy_load';
    const OPTION_CONSIDER_ALL_LACY = 'all_lazy';
    const OPTION_REFRESH = 'refresh';
    const OPTION_IDENTIFIER = 'identifier';


    /**
     * @var EntityManager
     */
    private $entityManager;

    /**
     * @var string
     */
    private $className;

    private $classMetadata;

    /**
     *
     * @param EntityManager $entityManager
     */
    public function __construct($className, EntityManager $entityManager)
    {
        $this->className = $className;
        $this->classMetadata = $entityManager->getClassMetadataFor($className);
        $this->entityManager = $entityManager;
    }


    public function hydrate(Record $record, array $options = [])
    {
        $andAddLazyLoad = isset($options[self::OPTION_ADD_LAZY_LOAD]) ? $options[self::OPTION_ADD_LAZY_LOAD] : false;
        $considerAllLazy = isset($options[self::OPTION_CONSIDER_ALL_LACY]) ? $options[self::OPTION_CONSIDER_ALL_LACY] : false;
        $refresh = isset($options[self::OPTION_REFRESH]) ? $options[self::OPTION_REFRESH] : false;
        $identifier = isset($options[self::OPTION_IDENTIFIER]) ? $options[self::OPTION_IDENTIFIER] : 'n';

        // getNodeBaseInstance
        $classMeta = $this->entityManager->getClassMetadataFor($this->className);
        $classN = $classMeta->getClassName();
        $baseInstance = $this->hydrateNode($record->get($identifier), $classN, $refresh);
        $cm = $this->entityManager->getClassMetadataFor($classN);
        foreach ($this->classMetadata->getSimpleRelationships(false) as $key => $association) {
            $relId = sprintf('%s_%s', strtolower($association->getPropertyName()), strtolower($association->getType()));
            $relKey = $association->isCollection() ? sprintf('rel_%s', $relId) : $association->getPropertyName();
            if ($record->hasValue($relKey) && null !== $record->get($relKey)) {
                if ($association->isCollection()) {
                    $association->initializeCollection($baseInstance);
                    foreach ($record->get($relKey) as $v) {
                        $nodeToUse = $association->getDirection() === 'OUTGOING' ? $v['end'] : $v['start'];
                        if ($association->getDirection() === 'BOTH') {
                            $baseId = $record->nodeValue($identifier)->identity();
                            $nodeToUse = $v['end']->identity() === $baseId ? $v['start'] : $v['end'];
                        }
                        $v2 = $this->hydrateNodeAndProxy($nodeToUse, $this->getTargetFullClassName($association->getTargetEntity()));
                        $association->addToCollection($baseInstance, $v2);
                        $this->entityManager->getUnitOfWork()->addManaged($v2);
                        $this->entityManager->getUnitOfWork()->addManagedRelationshipReference($baseInstance, $v2, $association->getPropertyName(), $association);
                        $this->setInversedAssociation($baseInstance, $v2, $association->getPropertyName());
                    }
                } else {
                    $hydrator = $this->getHydrator($this->getTargetFullClassName($association->getTargetEntity()));
                    $relO = $hydrator->hydrateNode($record->get($relKey), $association->getTargetEntity(), true);
                    $association->setValue($baseInstance, $relO);
                    $this->entityManager->getUnitOfWork()->addManagedRelationshipReference($baseInstance, $relO, $association->getPropertyName(), $association);
                    $this->setInversedAssociation($baseInstance, $relO, $relKey);
                }
            } else {
                if ($andAddLazyLoad && $association->isCollection() && $association->isLazy()) {
                    $lazy = new LazyRelationshipCollection($this->entityManager, $baseInstance, $association->getTargetEntity(), $association);
                    $association->setValue($baseInstance, $lazy);
                }
            }
        }

        foreach ($this->classMetadata->getRelationshipEntities() as $key => $relationshipEntity) {
            $class = $this->getTargetFullClassName($relationshipEntity->getRelationshipEntityClass());
            /** @var RelationshipEntityMetadata $reMetadata */
            $reMetadata = $this->entityManager->getRelationshipEntityMetadata($class);
            $recordKey = sprintf('rel_%s_%s', strtolower($relationshipEntity->getPropertyName()), strtolower($reMetadata->getType()));
            if (!$record->hasValue($recordKey) || null === $record->get($recordKey) || empty($record->get($recordKey))) {
                continue;
            }
            $startNodeMetadata = $this->entityManager->getClassMetadataFor($reMetadata->getStartNode());
            $endNodeMetadata = $this->entityManager->getClassMetadataFor($reMetadata->getEndNode());
            if ($relationshipEntity->isCollection()) {
                $v = new \GraphAware\Neo4j\OGM\Common\Collection();
                if (!is_array($record->get($recordKey))) {
                    throw new \LogicException('Expected array record value');
                }
                foreach ($record->get($recordKey) as $reMap) {
                    $oo2 = $this->hydrateRelationshipEntity(
                        $reMetadata, $reMap, $startNodeMetadata, $endNodeMetadata, $baseInstance, $relationshipEntity
                    );
                    $v->add($oo2);
                }
                $relationshipEntity->setValue($baseInstance, $v);
            } else {
                $reMap = $record->get($recordKey);
                if (!empty($reMap)) {
                    $reMap = $record->get($recordKey);
                    $relationshipEntity->setValue($baseInstance,
                        $this->hydrateRelationshipEntity(
                            $reMetadata, $reMap[0], $startNodeMetadata, $endNodeMetadata, $baseInstance, $relationshipEntity
                        ));
                }
            }
        }

        $lazyDone = [];

        foreach ($this->classMetadata->getLazyRelationships(true) as $relationship) {
            if (!$relationship->isRelationshipEntity()) {
                $lazyDone[] = $relationship->getPropertyName();
                $lazyCollection = new LazyRelationshipCollection($this->entityManager, $baseInstance, $relationship->getTargetEntity(), $relationship);
                $relationship->setValue($baseInstance, $lazyCollection);
                continue;
            }

            if ($relationship->isRelationshipEntity()) {
                if ($relationship->isCollection()) {
                    $lazyCollection = new LazyRelationshipCollection($this->entityManager, $baseInstance, $relationship->getRelationshipEntityClass(), $relationship);
                    $relationship->setValue($baseInstance, $lazyCollection);
                } else {
                }
            }
        }

        if ($considerAllLazy) {
            foreach ($this->classMetadata->getSimpleRelationships() as $relationship) {
                if ($relationship->isCollection()) {
                    if (!$relationship->isRelationshipEntity()) {
                        $lazyCollection = new LazyRelationshipCollection($this->entityManager, $baseInstance, $relationship->getTargetEntity(), $relationship);
                        $relationship->setValue($baseInstance, $lazyCollection);
                        continue;
                    }

                    if ($relationship->isRelationshipEntity()) {
                        $lazyCollection = new LazyRelationshipCollection($this->entityManager, $baseInstance, $relationship->getRelationshipEntityClass(), $relationship);
                        $relationship->setValue($baseInstance, $lazyCollection);
                    }
                }
            }
        }


        return $baseInstance;
    }

    private function setInversedAssociation($a, $b, $c)
    {
        $this->entityManager->getRepository($this->className)->setInversedAssociation($a, $b, $c);
    }

    private function hydrateRelationshipEntity(
        RelationshipEntityMetadata $reMetadata,
        array $reMap,
        NodeEntityMetadata $startNodeMetadata,
        NodeEntityMetadata $endNodeMetadata,
        $baseInstance,
        RelationshipMetadata $relationshipEntity, $pov = null)
    {
        /** @var \GraphAware\Neo4j\Client\Formatter\Type\Relationship $rel */
        $rel = $reMap['rel'];
        $relId = $rel->identity();
        if (null !== $possibleRE = $this->entityManager->getUnitOfWork()->getRelationshipEntityById($relId)) {
            return $possibleRE;
        }
        $start = $this->hydrateNodeAndProxy($reMap['start'], $startNodeMetadata->getClassName());
        $end = $this->hydrateNodeAndProxy($reMap['end'], $endNodeMetadata->getClassName());
        $reInstance = $reMetadata->newInstance();
        $reMetadata->setId($reInstance, $relId);
        $reMetadata->setStartNodeProperty($reInstance, $start);
        $reMetadata->setEndNodeProperty($reInstance, $end);
        $this->entityManager->getUnitOfWork()->addManagedRelationshipEntity($reInstance, $baseInstance, $relationshipEntity->getPropertyName());
        $reMetadata->setId($reInstance, $relId);
        $otherToSet = $relationshipEntity->getDirection() === 'INCOMING' ? $reMetadata->getStartNodeValue($reInstance) : $reMetadata->getEndNodeValue($reInstance);
        $possiblyMapped = $relationshipEntity->getDirection() === 'INCOMING' ? $reMetadata->getStartNodePropertyName() : $reMetadata->getEndNodePropertyName();
        $otherMetadata = $this->entityManager->getClassMetadataFor(get_class($otherToSet));
        foreach ($otherMetadata->getRelationships() as $relationship) {
            if ($relationship->getDirection() !== $relationshipEntity->getDirection() && $relationship->hasMappedByProperty() && $relationship->getMappedByProperty() === $possiblyMapped) {
                if ($relationship->isCollection()) {
                    if (null !== $this->entityManager->getUnitOfWork()->getEntityById($otherMetadata->getIdValue($otherToSet))) {
                        $relationship->initializeCollection($otherToSet);
                        $relationship->addToCollection($otherToSet, $reInstance);
                    }
                } else {
                    if (null === $relationship->getValue($otherToSet)) {
                        $relationship->setValue($otherToSet, $reInstance);
                    }
                }
            }
        }
        foreach ($rel->values() as $k => $value) {
            if (null !== $prop = $reMetadata->getPropertyMetadata($k)) {
                $prop->setValue($reInstance, $value);
            }
        }
        //$this->entityManager->getUnitOfWork()->addManagedRelationshipEntity($reInstance, $baseInstance, $relationshipEntity->getPropertyName());

        return $reInstance;
    }

    private function getHydrator($target)
    {
        return $this->entityManager->getHydrator($target);
    }

    private function hydrateNodeAndProxy(Node $node, $className = null)
    {
        if ($entity = $this->entityManager->getUnitOfWork()->getEntityById($node->identity())) {
            return $entity;
        }

        $cl = $className !== null ? $className : $this->className;
        $cm = $className === null ? $this->classMetadata : $this->entityManager->getClassMetadataFor($cl);
        $pmVersion = !method_exists(Version::class, 'getVersion') ? 1 : (int) Version::getVersion()[0];

        $em = $this->entityManager;
        if ($pmVersion >= 2) {
            $initializer = function ($ghostObject, $method, array $parameters, &$initializer, array $properties) use ($cm, $node, $em, $pmVersion) {
                $initializer = null;
                /*
                foreach ($cm->getPropertiesMetadata() as $field => $meta) {
                    if ($node->hasValue($field)) {

                        $key = null;
                        if ($meta->getReflectionProperty()->isPrivate()) {
                            $key = '\\0' . $cm->getClassName() . '\\0' . $meta->getPropertyName();
                        } else if($meta->getReflectionProperty()->isProtected()) {
                            $key = '' . "\0" . '*' . "\0" . $meta->getPropertyName();
                        } else if ($meta->getReflectionProperty()->isPublic()) {
                            $key = $meta->getPropertyName();
                        }

                        if (null !== $key) {
                            $properties[$key] = $node->value($field);
                        }

                        foreach ($cm->getLabeledProperties() as $labeledProperty) {
                            //$v = $node->hasLabel($labeledProperty->getLabelName()) ? true : false;
                            //$labeledProperty->setLabel($instance, $v);
                        }
                    }
                }
                */

                foreach ($cm->getSimpleRelationships(false) as $relationship) {
                    if (!$relationship->isCollection()) {
                        $finder = new RelationshipsFinder($em, $relationship->getTargetEntity(), $relationship);
                        $instances = $finder->find($node->identity());
                        if (count($instances) > 0) {
                            $properties[ProxyUtils::getPropertyIdentifier($relationship->getReflectionProperty(), $cm->getClassName())] = $instances[0];
                        }
                    }
                }

                return true;
            };
        } else {
            $initializer = function ($ghostObject, $method, array $parameters, &$initializer) use ($cm, $node, $em, $pmVersion) {
                $initializer = null;
                foreach ($cm->getPropertiesMetadata() as $field => $meta) {
                    if ($node->hasValue($field)) {
                        $meta->setValue($ghostObject, $node->value($field));
                    }
                }

                foreach ($cm->getSimpleRelationships(false) as $relationship) {
                    if (!$relationship->isCollection()) {
                        $finder = new RelationshipsFinder($em, $relationship->getTargetEntity(), $relationship);
                        $instances = $finder->find($node->identity());
                        if (count($instances) > 0) {
                            $relationship->setValue($ghostObject, $instances[0]);
                        }
                    }
                }

                $cm->setId($ghostObject, $node->identity());

                return true;
            };
        }

        $proxyOptions = [
            'skippedProperties' => [
                ''."\0".'*'."\0".'id',
            ],
        ];

        $instance = 2 === $pmVersion ? $this->lazyLoadingFactory->createProxy($cm->getClassName(), $initializer, $proxyOptions) : $this->lazyLoadingFactory->createProxy($cm->getClassName(), $initializer);
        foreach ($cm->getPropertiesMetadata() as $field => $propertyMetadata) {
            if ($node->hasValue($field)) {
                $propertyMetadata->setValue($instance, $node->value($field));
            }
        }

        $cm->setId($instance, $node->identity());

        foreach ($cm->getRelationships() as $relationship) {
            if (!$relationship->isRelationshipEntity()) {
                if ($relationship->isCollection()) {
                    $lazyCollection = new LazyRelationshipCollection($this->entityManager, $instance, $relationship->getTargetEntity(), $relationship);
                    $relationship->setValue($instance, $lazyCollection);
                    continue;
                }
            }

            if ($relationship->isRelationshipEntity()) {
                if ($relationship->isCollection()) {
                    $lazyCollection = new LazyRelationshipCollection($this->entityManager, $instance, $relationship->getRelationshipEntityClass(), $relationship);
                    $relationship->setValue($instance, $lazyCollection);
                } else {
                }
            }
        }

        $i2 = clone $instance;
        $this->entityManager->getUnitOfWork()->addManaged($i2);

        return $i2;
    }

    /**
     * Create a instance of the object and populate it with data.
     *
     * @param Node $node
     * @param string|null $className
     * @param bool $refresh
     *
     * @return null|object
     */
    private function hydrateNode(Node $node, $className = null, $refresh = false)
    {
        $entity = $this->entityManager->getUnitOfWork()->getEntityById($node->identity());
        if ($entity && !$refresh) {
            return $entity;
        }

        $cl = $className !== null ? $className : $this->className;
        $cm = $className === null ? $this->classMetadata : $this->entityManager->getClassMetadataFor($cl);

        if ($refresh) {
            $instance = $entity;
        } else {
            $instance = $cm->newInstance();
        }

        $instance = $this->entityManager->getProxyFactory($this->classMetadata)->fromNode($node);

        $this->populateDataToInstance($node, $cm, $instance);

        $this->entityManager->getUnitOfWork()->addManaged($instance);

        return $instance;
    }


    public function hydrateQueryRecord(QueryResultMapper $resultMapper, Record $record)
    {
        $reflClass = new \ReflectionClass($resultMapper->getClassName());
        $instance = $reflClass->newInstanceWithoutConstructor();
        foreach ($resultMapper->getFields() as $field) {
            if (!$record->hasValue($field->getFieldName())) {
                throw new \RuntimeException(sprintf('The record doesn\'t contain the required field "%s"', $field->getFieldName()));
            }
            $value = null;
            if ($field->isEntity()) {
                $value = $this->getNodeBaseInstance($record, $field->getFieldName(), ClassUtils::getFullClassName($field->getTarget(), $resultMapper->getClassName()));
            } else {
                $value = $record->get($field->getFieldName());
            }
            $property = $reflClass->getProperty($field->getFieldName());
            $property->setAccessible(true);
            $property->setValue($instance, $value);
        }

        return $instance;
    }

    private function getNodeBaseInstance(Record $record, $identifier = 'n', $className = null)
    {

        $classN = null !== $className ? $className : $this->className;
        $baseInstance = $this->hydrateNode($record->get($identifier), $classN);
        $cm = $this->entityManager->getClassMetadataFor($classN);

        return $baseInstance;
    }

    /**
     * Hydrate a complete result set.
     * @param Result $result
     *
     * @return array
     */
    public function hydrateResultSet(Result $result)
    {
        $entities = [];
        foreach ($result->records() as $record) {
            $entities[] = $this->hydrate($record);
        }

        return $entities;
    }

    /**
     * @param $className
     *
     * @return string
     */
    private function getTargetFullClassName($className)
    {
        $expl = explode('\\', $className);
        if (1 === count($expl)) {
            $expl2 = explode('\\', $this->className);
            if (1 !== count($expl2)) {
                unset($expl2[count($expl2) - 1]);
                $className = implode('\\', $expl2).'\\'.$className;
            }
        }

        if (!class_exists($className)) {
            throw new \LogicException(sprintf('Guessed class name "%s" doesn\'t exist', $className));
        }

        return $className;
    }

    /**
     * @param Node $node
     * @param NodeEntityMetadata $cm
     * @param object$instance
     */
    public function populateDataToInstance(Node $node, $cm, $instance)
    {
        foreach ($cm->getPropertiesMetadata() as $field => $meta) {
            if ($meta instanceof EntityPropertyMetadata) {
                if ($node->hasValue($field)) {
                    $meta->setValue($instance, $node->value($field));
                }
            } elseif ($meta instanceof Label) {
                $label = $meta->name;
                /*
                $v = $node->hasLabel($label);
                if ($property = $reflClass->getProperty($field)) {
                    $property->setAccessible(true);
                    $property->setValue($instance, $v);
                }
                */
            }
        }

        foreach ($cm->getLabeledProperties() as $labeledProperty) {
            $v = $node->hasLabel($labeledProperty->getLabelName()) ? true : false;
            $labeledProperty->setLabel($instance, $v);
        }

        foreach ($cm->getRelationships() as $relationship) {
            if ($relationship->isCollection()) {
                $relationship->initializeCollection($instance);
            }
        }

        $cm->setId($instance, $node->identity());
    }

}
