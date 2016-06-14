<?php

namespace GraphAware\Neo4j\OGM\Repository;

use Doctrine\Common\Collections\ArrayCollection;
use GraphAware\Common\Result\Record;
use GraphAware\Common\Type\Node;
use GraphAware\Common\Result\Result;
use GraphAware\Neo4j\OGM\EntityManager;
use GraphAware\Neo4j\OGM\Metadata\EntityPropertyMetadata;
use GraphAware\Neo4j\OGM\Metadata\NodeEntityMetadata;
use GraphAware\Neo4j\OGM\Metadata\QueryResultMapper;
use GraphAware\Neo4j\OGM\Metadata\RelationshipEntityMetadata;
use GraphAware\Neo4j\OGM\Metadata\RelationshipMetadata;
use GraphAware\Neo4j\OGM\Query\QueryResultMapping;
use GraphAware\Neo4j\OGM\Annotations\Label;
use GraphAware\Neo4j\OGM\Util\ClassUtils;

class BaseRepository
{
    const FILTER_LIMIT = 'limit';

    const FILTER_ORDER = 'order';

    const ORDER_ASC = 'ASC';

    const ORDER_DESC = 'DESC';

    /**
     * @var \GraphAware\Neo4j\OGM\Metadata\ClassMetadata
     */
    protected $classMetadata;

    /**
     * @var \GraphAware\Neo4j\OGM\EntityManager
     */
    protected $entityManager;

    /**
     * @var string
     */
    protected $className;

    /**
     * @var \ReflectionClass
     */
    protected $reflectionClass;

    /**
     * @var \ReflectionClass[]
     */
    protected $loadedReflClasses = [];

    /**
     * @param \GraphAware\Neo4j\OGM\Metadata\ClassMetadata $classMetadata
     * @param \GraphAware\Neo4j\OGM\EntityManager                $manager
     * @param string                                       $className
     */
    public function __construct(NodeEntityMetadata $classMetadata, EntityManager $manager, $className)
    {
        $this->classMetadata = $classMetadata;
        $this->entityManager = $manager;
        $this->className = $className;
    }

    /**
     * @return object[]
     *
     * @throws \GraphAware\Neo4j\Client\Exception\Neo4jException
     */
    public function findAll(array $filters = array())
    {
        $parameters = [];
        $label = $this->classMetadata->getLabel();
        $query = sprintf('MATCH (n:%s)', $label);
        /** @var RelationshipMetadata[] $associations */
        $associations = $this->classMetadata->getRelationships();
        $assocReturns = [];
        foreach ($associations as $identifier => $association) {
            switch ($association->getDirection()) {
                case 'INCOMING':
                    $relStr = '<-[rel_%s:%s]-';
                    break;
                case 'OUTGOING':
                    $relStr = '-[rel_%s:%s]->';
                    break;
                default:
                    $relStr = '-[rel_%s:%s]-';
                    break;
            }

            $relationshipIdentifier = sprintf('%s_%s', strtolower($association->getPropertyName()), strtolower($association->getType()));
            $relQueryPart = sprintf($relStr, $relationshipIdentifier, $association->getType());
            $query .= PHP_EOL;
            $query .= 'OPTIONAL MATCH (n)'.$relQueryPart.'('.$association->getPropertyName().')';
            $query .= ' WITH n, ';
            $query .= implode(', ', $assocReturns);
            if (!empty($assocReturns)) {
                $query .= ', ';
            }
            $relid = $relid = 'rel_'.$relationshipIdentifier;
            if ($association->isCollection() || $association->isRelationshipEntity()) {
                $query .= sprintf(' CASE count(%s) WHEN 0 THEN [] ELSE collect({start:startNode(%s), end:endNode(%s), rel:%s}) END as %s', $relid, $relid, $relid, $relid, $relid);
                $assocReturns[] = $relid;
            } else {
                $query .= $association->getPropertyName();
                $assocReturns[] = $association->getPropertyName();
            }
        }

        $query .= PHP_EOL;
        $query .= 'RETURN n';
        if (!empty($assocReturns)) {
            $query .= ', ' . implode(', ', $assocReturns);
        }

        if (isset($filters[self::FILTER_ORDER])) {
            foreach ($filters[self::FILTER_ORDER] as $key => $filter) {
                if (array_key_exists($key, $this->classMetadata->getPropertiesMetadata())) {
                    $query .= sprintf(' ORDER BY n.%s %s', $key, $filter);
                }
            }
        }

        if (isset($filters[self::FILTER_LIMIT]) && is_numeric($filters[self::FILTER_LIMIT])) {
            $query .= ' LIMIT {limit}';
            $parameters[self::FILTER_LIMIT] = $filters[self::FILTER_LIMIT];
        }

        $result = $this->entityManager->getDatabaseDriver()->run($query, $parameters);

        return $this->hydrateResultSet($result);
    }

    /**
     * @param string $key
     * @param mixed  $value
     * @param bool isId
     * @param bool $match
     *
     * @return object[]
     *
     * @throws \GraphAware\Neo4j\Client\Exception\Neo4jException
     */
    private function findByQuery($key, $value, $isId = false, $match = false)
    {
        $label = $this->classMetadata->getLabel();
        $idId = $isId ? 'id(n)' : sprintf('n.%s', $key);
        $matchRegexp = $match ? '=~' : '-';

        $query = sprintf('MATCH (n:%s) WHERE %s %s {%s}', $label, $idId, $matchRegexp, $key);

        /** @var \GraphAware\Neo4j\OGM\Metadata\RelationshipMetadata[] $associations */
        $associations = $this->classMetadata->getRelationships();
        $assocReturns = [];
        foreach ($associations as $identifier => $association) {
            switch ($association->getDirection()) {
                case 'INCOMING':
                    $relStr = '<-[rel_%s:%s]-';
                    break;
                case 'OUTGOING':
                    $relStr = '-[rel_%s:%s]->';
                    break;
                default:
                    $relStr = '-[rel_%s:%s]-';
                    break;
            }

            $relationshipIdentifier = sprintf('%s_%s', strtolower($association->getPropertyName()), strtolower($association->getType()));
            $relQueryPart = sprintf($relStr, $relationshipIdentifier, $association->getType());
            $query .= PHP_EOL;
            $query .= 'OPTIONAL MATCH (n)'.$relQueryPart.'('.$association->getPropertyName().')';
            $query .= ' WITH n, ';
            $query .= implode(', ', $assocReturns);
            if (!empty($assocReturns)) {
                $query .= ', ';
            }
            $relid = 'rel_'.$relationshipIdentifier;
            if ($association->isCollection() || $association->isRelationshipEntity()) {
                $query .= sprintf(' CASE count(%s) WHEN 0 THEN [] ELSE collect({start:startNode(%s), end:endNode(%s), rel:%s}) END as %s', $relid, $relid, $relid, $relid, $relid);
                $assocReturns[] = $relid;
            } else {
                $query .= $association->getPropertyName();
                $assocReturns[] = $association->getPropertyName();
            }
        }

        $query .= PHP_EOL;
        $query .= 'RETURN n';
        if (!empty($assocReturns)) {
            $query .= ', ' . implode(', ', $assocReturns);
        }

        $parameters = [$key => $value];
        $result = $this->entityManager->getDatabaseDriver()->run($query, $parameters);

        return $this->hydrateResultSet($result);
    }

    /**
     * @param string $key
     * @param mixed  $value
     *
     * @return object[]
     *
     * @throws \GraphAware\Neo4j\Client\Exception\Neo4jException
     */
    public function findBy($key, $value, $isId = false)
    {
        return $this->findByQuery($key, $value, $isId, false);
    }

     /**
     * Matches results by regexp. To make it easier, the options to case insensitive and starts/ends with are default
     * Send null on startsWith and endsWith when you want to use your own regexp
     *
     * @param string $key
     * @param mixed  $value
     * @param bool $caseInsensitive
     * @param bool $startsWith
     * @param bool $endsWith
     *
     * @return object[]
     *
     * @throws \GraphAware\Neo4j\Client\Exception\Neo4jException
     */
    public function matchBy($key, $value, $caseInsensitive=true, $startsWith=true, $endsWith=true)
    {
        if($startsWith !== null) {
            $value = $startsWith ? sprintf("$value%s", '.*') : $value;
        }

        if($endsWith !== null) {
            $value = $endsWith ? sprintf("%s$value", '.*') : $value;
        }

        $value = $caseInsensitive ? sprintf("%s$value", '(?i)') : $value;

        return $this->findByQuery($key, $value, false, true);
    }

    /**
     * Matches a result by regexp. To make it easier, the options to case insensitive and starts/ends with are default
     * Send null on startsWith and endsWith when you want to use your own regexp
     *
     * @param string $key
     * @param mixed  $value
     * @param bool $caseInsensitive
     * @param bool $startsWith
     * @param bool $endsWith
     *
     * @return object[]
     *
     * @throws \GraphAware\Neo4j\Client\Exception\Neo4jException
     */
    public function matchOneBy($key, $value, $caseInsensitive=true, $startsWith=true, $endsWith=true)
    {
        $instances = $this->matchBy($key, $value, $caseInsensitive, $startsWith, $endsWith);

        if (count($instances) > 1) {
            throw new \Exception('Expected only one result, got '.count($instances));
        }

        return isset($instances[0]) ? $instances[0] : null;
    }

    /**
     * @param string $key
     * @param mixed  $value
     *
     * @return null|object
     *
     * @throws \Exception
     */
    public function findOneBy($key, $value)
    {
        $instances = $this->findBy($key, $value);

        if (count($instances) > 1) {
            throw new \Exception('Expected only one result, got '.count($instances));
        }

        return isset($instances[0]) ? $instances[0] : null;
    }

    public function findOneById($id)
    {
        $hydrated = $this->findBy('id', $id, true);

        return isset($hydrated[0]) ? $hydrated[0] : null;
    }

    protected function nativeQuery($query, $parameters = null, QueryResultMapping $resultMapping)
    {
        $parameters = null !== $parameters ? (array) $parameters : array();
        $result = $this->entityManager->getDatabaseDriver()->run($query, $parameters);
        if ($result->size() < 1) {
            return;
        }

        if ($result->size() > 1 && $resultMapping->getQueryResultType() !== QueryResultMapping::RESULT_MULTIPLE) {
            throw new \RuntimeException(sprintf('Expected a single record, got %d', $result->size()));
        }

        $results = [];
        $mappingMetadata = $this->entityManager->getResultMappingMetadata($resultMapping->getQueryResultClass());
        foreach ($result->records() as $record) {
            $results[] = $this->hydrateQueryRecord($mappingMetadata, $record);
        }

        return $resultMapping->getQueryResultType() === QueryResultMapping::RESULT_SINGLE ? $results[0] : $results;
    }

    private function hydrateQueryRecord(QueryResultMapper $resultMapper, Record $record)
    {
        $reflClass = new \ReflectionClass($resultMapper->getClassName());
        $instance = $reflClass->newInstanceWithoutConstructor();
        foreach ($resultMapper->getFields() as $field) {
            if (!$record->hasValue($field->getFieldName())) {
                throw new \RuntimeException(sprintf('The record doesn\'t contain the required field "%s"', $field->getFieldName()));
            }
            $value = null;
            if ($field->isEntity()) {
                $value = $this->hydrate($record, false, $field->getFieldName(), ClassUtils::getFullClassName($field->getTarget(), $resultMapper->getClassName()));
            } else {
                $value = $record->get($field->getFieldName());
            }
            $property = $reflClass->getProperty($field->getFieldName());
            $property->setAccessible(true);
            $property->setValue($instance, $value);
        }

        return $instance;
    }

    private function hydrateResultSet(Result $result)
    {
        $entities = [];
        foreach ($result->records() as $record) {
            $entities[] = $this->hydrate($record);
        }

        return $entities;
    }

    private function hydrate(Record $record, $andCheckAssociations = true, $identifier = 'n', $className = null)
    {
        $classN = null !== $className ? $className : $this->className;
        $baseInstance = $this->hydrateNode($record->get($identifier), $classN);
        if ($andCheckAssociations) {
            foreach ($this->classMetadata->getSimpleRelationships() as $key => $association) {
                $relId = sprintf('%s_%s', strtolower($association->getPropertyName()), strtolower($association->getType()));
                $relKey = $association->isCollection() ? sprintf('rel_%s', $relId) : $association->getPropertyName();
                if ($record->hasValue($relKey) && null !== $record->get($relKey)) {
                    if ($association->isCollection()) {
                        $association->initializeCollection($baseInstance);
                        foreach ($record->get($relKey) as $v) {
                            $nodeToUse = $association->getDirection() === "OUTGOING" ? $v['end'] : $v['start'];
                            if ($association->getDirection() === 'BOTH') {
                                $baseId = $record->nodeValue($identifier)->identity();
                                $nodeToUse = $v['end']->identity() === $baseId ? $v['start'] : $v['end'];
                            }
                            $v2 = $this->hydrateNode($nodeToUse, $this->getTargetFullClassName($association->getTargetEntity()));
                            $association->addToCollection($baseInstance, $v2);
                            $this->entityManager->getUnitOfWork()->addManagedRelationshipReference($baseInstance, $v2, $association->getPropertyName(), $association);
                            $this->setInversedAssociation($baseInstance, $v2, $association->getPropertyName());
                        }
                    } else {
                        $hydrator = $this->getHydrator($this->getTargetFullClassName($association->getTargetEntity()));
                        $relO = $hydrator->hydrateNode($record->get($relKey));
                        $association->setValue($baseInstance, $relO);
                        $this->setInversedAssociation($baseInstance, $relO, $relKey);
                    }
                }
            }

            foreach ($this->classMetadata->getRelationshipEntities() as $key => $relationshipEntity) {
                $recordKey = sprintf('rel_%s_%s', strtolower($relationshipEntity->getPropertyName()), strtolower($relationshipEntity->getType()));
                if (null === $record->get($recordKey) || empty($record->get($recordKey))) {
                    continue;
                }
                $class = $this->getTargetFullClassName($relationshipEntity->getRelationshipEntityClass());
                /** @var RelationshipEntityMetadata $reMetadata */
                $reMetadata = $this->entityManager->getRelationshipEntityMetadata($class);
                $startNodeMetadata = $this->entityManager->getClassMetadataFor($reMetadata->getStartNode());
                $endNodeMetadata = $this->entityManager->getClassMetadataFor($reMetadata->getEndNode());
                if ($relationshipEntity->isCollection()) {
                    $v = new \GraphAware\Neo4j\OGM\Common\Collection();
                    if (!is_array($record->get($recordKey))) {
                        throw new \LogicException('Expected array record value');
                    }
                    foreach ($record->get($recordKey) as $reMap) {
                        $v->add($this->hydrateRelationshipEntity(
                            $reMetadata, $reMap, $startNodeMetadata, $endNodeMetadata, $baseInstance, $relationshipEntity
                        ));
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
        }

        return $baseInstance;
    }

    private function hydrateRelationshipEntity(
        RelationshipEntityMetadata $reMetadata,
        array $reMap,
        NodeEntityMetadata $startNodeMetadata,
        NodeEntityMetadata $endNodeMetadata,
        $baseInstance,
        RelationshipMetadata $relationshipEntity)
    {
        $reInstance = $reMetadata->newInstance();
        $start = $this->hydrateNode($reMap['start'], $startNodeMetadata->getClassName());
        $end = $this->hydrateNode($reMap['end'], $endNodeMetadata->getClassName());
        /** @var \GraphAware\Neo4j\Client\Formatter\Type\Relationship $rel */
        $rel = $reMap['rel'];
        $relId = $rel->identity();
        $reMetadata->setStartNodeProperty($reInstance, $start);
        $reMetadata->setEndNodeProperty($reInstance, $end);
        $reMetadata->setId($reInstance, $relId);
        foreach ($reMetadata->getPropertiesMetadata() as $field) {
            if ($rel->hasValue($field->getPropertyName())) {
                $reMetadata->getPropertyMetadata($field->getPropertyName())->setValue($reInstance, $rel->get($field->getPropertyName()));
            }
        }
        $this->entityManager->getUnitOfWork()->addManagedRelationshipEntity($reInstance, $baseInstance, $relationshipEntity->getPropertyName());

        return $reInstance;
    }

    private function getHydrator($target)
    {
        return $this->entityManager->getRepository($target);
    }

    private function hydrateNode(Node $node, $className = null)
    {
        if ($entity = $this->entityManager->getUnitOfWork()->getEntityById($node->identity())) {
            return $entity;
        }
        $cl = $className !== null ? $className : $this->className;
        $cm = $className === null ? $this->classMetadata : $this->entityManager->getClassMetadataFor($cl);
        $instance = $cm->newInstance();
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
        $this->entityManager->getUnitOfWork()->addManaged($instance);

        return $instance;
    }

    private function setInversedAssociation($baseInstance, $otherInstance, $relationshipKey)
    {
        $assoc = $this->classMetadata->getRelationship($relationshipKey);
        if ($assoc->hasMappedByProperty()) {
            $mappedBy = $assoc->getMappedByProperty();
            $reflClass = $this->getReflectionClass(get_class($otherInstance));
            $property = $reflClass->getProperty($mappedBy);
            $property->setAccessible(true);
            $otherClassMetadata = $this->entityManager->getClassMetadataFor(get_class($otherInstance));
            if ($otherClassMetadata->getRelationship($mappedBy)->isCollection()) {
                if (null === $property->getValue($otherInstance)) {
                    $property->setValue($otherInstance, new ArrayCollection());
                }
                $property->getValue($otherInstance)->add($baseInstance);
            } else {
                $property->setValue($otherInstance, $baseInstance);
            }
        }
    }

    /**
     * @param $className
     *
     * @return \ReflectionClass
     */
    private function getReflectionClass($className)
    {
        if (!array_key_exists($className, $this->loadedReflClasses)) {
            $this->loadedReflClasses[$className] = new \ReflectionClass($className);
        }

        return $this->loadedReflClasses[$className];
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
}
