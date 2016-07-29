<?php

namespace GraphAware\Neo4j\OGM\Repository;

use GraphAware\Common\Result\Record;
use GraphAware\Common\Type\Node;
use GraphAware\Common\Result\Result;
use GraphAware\Neo4j\OGM\EntityManager;
use GraphAware\Neo4j\OGM\Finder\RelationshipsFinder;
use GraphAware\Neo4j\OGM\Lazy\LazyRelationshipCollection;
use GraphAware\Neo4j\OGM\Metadata\EntityPropertyMetadata;
use GraphAware\Neo4j\OGM\Metadata\NodeEntityMetadata;
use GraphAware\Neo4j\OGM\Metadata\QueryResultMapper;
use GraphAware\Neo4j\OGM\Metadata\RelationshipEntityMetadata;
use GraphAware\Neo4j\OGM\Metadata\RelationshipMetadata;
use GraphAware\Neo4j\OGM\Query\Pagination;
use GraphAware\Neo4j\OGM\Query\QueryResultMapping;
use GraphAware\Neo4j\OGM\Annotations\Label;
use GraphAware\Neo4j\OGM\Util\ClassUtils;
use GraphAware\Neo4j\OGM\Util\ProxyUtils;
use ProxyManager\Configuration;
use ProxyManager\Factory\LazyLoadingGhostFactory;
use ProxyManager\FileLocator\FileLocator;
use ProxyManager\GeneratorStrategy\FileWriterGeneratorStrategy;
use ProxyManager\Version;

class BaseRepository
{
    const FILTER_LIMIT = 'limit';

    const FILTER_ORDER = 'order';

    const ORDER_ASC = 'ASC';

    const ORDER_DESC = 'DESC';

    private static $PAGINATION_FIRST_RESULT_KEY = "first";
    private static $PAGINATION_LIMIT_RESULTS_KEY = "max";

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

    protected $lazyLoadingFactory;

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
        $config = new Configuration();
        $dir = sys_get_temp_dir();
        $config->setGeneratorStrategy(new FileWriterGeneratorStrategy(new FileLocator($dir)));
        $config->setProxiesTargetDir($dir);
        spl_autoload_register($config->getProxyAutoloader());

        $this->lazyLoadingFactory = new LazyLoadingGhostFactory($config);
    }

    /**
     * @return object[]
     *
     * @throws \GraphAware\Neo4j\Client\Exception\Neo4jException
     */
    public function findAll(array $filters = array())
    {
        $pagination = $this->getPagination($filters);
        $parameters = [];
        $label = $this->classMetadata->getLabel();
        $query = sprintf('MATCH (n:%s)', $label);

        if (null !== $pagination) {
            $query .= ' WITH n ORDER BY ';
            if (null !== $pagination->getOrderBy()) {
                $query .= 'n.' . $pagination->getOrderBy()[0] . ' ' . $pagination->getOrderBy()[1] . ' ';
            } else {
                $query .= 'id(n) ASC ';
            }

            $query .= ' SKIP {skip} LIMIT {limit}';

            $parameters['skip'] = $pagination->getFirst();
            $parameters['limit'] = $pagination->getMax();
        }


        /** @var RelationshipMetadata[] $associations */
        $associations = $this->classMetadata->getRelationships();
        $assocReturns = [];
        foreach ($associations as $identifier => $association) {
            $type = $association->isRelationshipEntity() ? $this->entityManager->getRelationshipEntityMetadata($association->getRelationshipEntityClass())->getType() : $association->getType();
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

            $relationshipIdentifier = sprintf('%s_%s', strtolower($association->getPropertyName()), strtolower($type));
            $relQueryPart = sprintf($relStr, $relationshipIdentifier, $type);
            $query .= PHP_EOL;
            $query .= 'OPTIONAL MATCH (n)'.$relQueryPart.'('.$association->getPropertyName().')';
            $query .= ' WITH n, ';
            $query .= implode(', ', $assocReturns);
            if (!empty($assocReturns)) {
                $query .= ', ';
            }
            $relid = $relid = 'rel_'.$relationshipIdentifier;
            if ($association->hasOrderBy()) {
                $orderProperty = $association->getPropertyName() . '.' . $association->getOrderByPropery();
                if ($association->isRelationshipEntity()) {
                    $reMetadata = $this->entityManager->getRelationshipEntityMetadata($association->getRelationshipEntityClass());
                    $split = explode('.', $association->getOrderByPropery());
                    if (count($split) > 1) {
                        $reName = $split[0];
                        $v = $split[1];
                        if ($reMetadata->getStartNodePropertyName() === $reName || $reMetadata->getEndNodePropertyName() === $reName) {
                            $orderProperty = $association->getPropertyName() . '.' . $v;
                        }
                    } else {
                        if (null !== $reMetadata->getPropertyMetadata($association->getOrderByPropery())) {
                            $orderProperty = $relid . '.' . $association->getOrderByPropery();
                        }
                    }
                }
                $query .= $relid . ', ' . $association->getPropertyName() . ' ORDER BY ' . $orderProperty . ' ' . $association->getOrder();
                $query .= PHP_EOL;
                $query .= ' WITH n, ';
                $query .= implode(', ', $assocReturns);
                if (!empty($assocReturns)) {
                    $query .= ', ';
                }
            }
            if ($association->isCollection() || $association->isRelationshipEntity()) {
                $query .= sprintf(' CASE count(%s) WHEN 0 THEN [] ELSE collect({start:startNode(%s), end:endNode(%s), rel:%s}) END as %s', $relid, $relid, $relid, $relid, $relid);
                $assocReturns[] = $relid;
            } else {
                $query .= $association->getPropertyName();
                $assocReturns[] = $association->getPropertyName();
            }
        }

        if (null !== $pagination) {
            $query .= ' WITH n';

            if (!empty($assocReturns)) {
                $query .= ', ' . implode(',', $assocReturns);
            }

            $query .= ' ORDER BY ';
            if (null !== $pagination->getOrderBy()) {
                $query .= 'n.' . $pagination->getOrderBy()[0] . ' ' . $pagination->getOrderBy()[1] . ' ';
            } else {
                $query .= 'id(n) ASC ';
            }

            $parameters['skip'] = $pagination->getFirst();
            $parameters['limit'] = $pagination->getMax();
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

        $tag = array(
            'class' => BaseRepository::class,
            'method' => 'findAll',
            'arguments' => $filters
        );

        $result = $this->entityManager->getDatabaseDriver()->run($query, $parameters, json_encode($tag));

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
        $label = $this->classMetadata->getLabel();
        $idId = $isId ? 'id(n)' : sprintf('n.%s', $key);
        $query = sprintf('MATCH (n:%s) WHERE %s = {%s}', $label, $idId, $key);
        /** @var \GraphAware\Neo4j\OGM\Metadata\RelationshipMetadata[] $associations */
        $associations = $this->classMetadata->getNonLazyRelationships();
        $assocReturns = [];
        foreach ($associations as $identifier => $association) {
            $type = $association->isRelationshipEntity() ? $this->entityManager->getRelationshipEntityMetadata($association->getRelationshipEntityClass())->getType() : $association->getType();
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

            $relationshipIdentifier = sprintf('%s_%s', strtolower($association->getPropertyName()), strtolower($type));
            $relQueryPart = sprintf($relStr, $relationshipIdentifier, $type);
            $query .= PHP_EOL;
            $query .= 'OPTIONAL MATCH (n)'.$relQueryPart.'('.$association->getPropertyName().')';
            $query .= ' WITH n, ';
            $query .= implode(', ', $assocReturns);
            if (!empty($assocReturns)) {
                $query .= ', ';
            }
            $relid = 'rel_'.$relationshipIdentifier;
            if ($association->hasOrderBy()) {
                $orderProperty = $association->getPropertyName() . '.' . $association->getOrderByPropery();
                if ($association->isRelationshipEntity()) {
                    $reMetadata = $this->entityManager->getRelationshipEntityMetadata($association->getRelationshipEntityClass());
                    $split = explode('.', $association->getOrderByPropery());
                    if (count($split) > 1) {
                        $reName = $split[0];
                        $v = $split[1];
                        if ($reMetadata->getStartNodePropertyName() === $reName || $reMetadata->getEndNodePropertyName() === $reName) {
                            $orderProperty = $association->getPropertyName() . '.' . $v;
                        }
                    } else {
                        if (null !== $reMetadata->getPropertyMetadata($association->getOrderByPropery())) {
                            $orderProperty = $relid . '.' . $association->getOrderByPropery();
                        }
                    }
                }
                $query .= $relid . ', ' . $association->getPropertyName() . ' ORDER BY ' . $orderProperty . ' ' . $association->getOrder();
                $query .= PHP_EOL;
                $query .= ' WITH n, ';
                $query .= implode(', ', $assocReturns);
                if (!empty($assocReturns)) {
                    $query .= ', ';
                }
            }
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

        //print_r($query);

        $parameters = [$key => $value];
        $result = $this->entityManager->getDatabaseDriver()->run($query, $parameters);

        return $this->hydrateResultSet($result);
    }

    public function paginated($first, $max, array $order = array())
    {
        return $this->findAll(['first' => $first, 'max' => $max, 'order' => $order]);
    }

    private function getPagination(array $filters)
    {
        return Pagination::create($filters);
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
        $hydrated = $this->findBy('id', (int)$id, true);

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

    public function hydrate(Record $record, $andCheckAssociations = true, $identifier = 'n', $className = null, $andAddLazyLoad = false, $considerAllLazy = false)
    {
        $classN = null !== $className ? $className : $this->className;
        $baseInstance = $this->hydrateNode($record->get($identifier), $classN);
        $cm = $this->entityManager->getClassMetadataFor($classN);
        if ($andCheckAssociations) {
            foreach ($this->classMetadata->getSimpleRelationships(false) as $key => $association) {
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
                            $v2 = $this->hydrateNode($nodeToUse, $this->getTargetFullClassName($association->getTargetEntity()), true);
                            $association->addToCollection($baseInstance, $v2);
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

            foreach ($this->classMetadata->getLazyRelationships(true) as $relationship) {
                if (!$relationship->isRelationshipEntity()) {
                    $lazyCollection = new LazyRelationshipCollection($this->entityManager, $baseInstance, $relationship->getTargetEntity(), $relationship);
                    $relationship->setValue($baseInstance, $lazyCollection);
                    continue;
                }

                if ($relationship->isRelationshipEntity()) {
                    if ($relationship->isCollection()) {
                        $lazyCollection = new LazyRelationshipCollection($this->entityManager, $baseInstance, $relationship->getRelationshipEntityClass(), $relationship);
                        $relationship->setValue($baseInstance, $lazyCollection);
                    } else {
                        //
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
        }

        return $baseInstance;
    }

    public function hydrateRelationshipEntity(
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
        $start = $this->hydrateNode($reMap['start'], $startNodeMetadata->getClassName(), true);
        $end = $this->hydrateNode($reMap['end'], $endNodeMetadata->getClassName(), true);
        $reInstance = $reMetadata->newInstance();
        $reMetadata->setId($reInstance, $relId);
        $reMetadata->setStartNodeProperty($reInstance, $start);
        $reMetadata->setEndNodeProperty($reInstance, $end);
        $this->entityManager->getUnitOfWork()->addManagedRelationshipEntity($reInstance, $baseInstance, $relationshipEntity->getPropertyName());
        $reMetadata->setId($reInstance, $relId);
        $otherToSet = $relationshipEntity->getDirection() === "INCOMING" ? $reMetadata->getStartNodeValue($reInstance) : $reMetadata->getEndNodeValue($reInstance);
        $possiblyMapped = $relationshipEntity->getDirection() === "INCOMING" ? $reMetadata->getStartNodePropertyName() : $reMetadata->getEndNodePropertyName();
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
        return $this->entityManager->getRepository($target);
    }

    private function hydrateNode(Node $node, $className = null, $andProxy = false)
    {
        if ($entity = $this->entityManager->getUnitOfWork()->getEntityById($node->identity())) {
            return $entity;
        }
        $cl = $className !== null ? $className : $this->className;
        $cm = $className === null ? $this->classMetadata : $this->entityManager->getClassMetadataFor($cl);
        $pmVersion = !method_exists(Version::class, 'getVersion') ? 1 : (int) Version::getVersion()[0];

        $em = $this->entityManager;
        if ($andProxy) {
            if ($pmVersion >= 2) {
                $initializer = function($ghostObject, $method, array $parameters, & $initializer, array $properties) use ($cm, $node, $em, $pmVersion) {
                    $initializer = null;
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
                $initializer = function($ghostObject, $method, array $parameters, & $initializer) use ($cm, $node, $em, $pmVersion) {
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
                    '' . "\0" . '*' . "\0" . 'id'
                ]
            ];



            $instance = 2 === $pmVersion ? $this->lazyLoadingFactory->createProxy($cm->getClassName(), $initializer, $proxyOptions) : $this->lazyLoadingFactory->createProxy($cm->getClassName(), $initializer);
            if (2 === $pmVersion) {
                $cm->setId($instance, $node->identity());
            }
            $cm->setId($instance, $node->identity());
            $i2 = clone($instance);
            $this->entityManager->getUnitOfWork()->addManaged($i2);

            return $i2;
        }


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
                    $mt = $otherClassMetadata->getRelationship($mappedBy);
                    $lazy = new LazyRelationshipCollection($this->entityManager, $otherInstance, $mt->getTargetEntity(), $mt, $baseInstance);
                    $property->setValue($otherInstance, $lazy);
                } else {
                    $property->getValue($otherInstance)->add($baseInstance);
                }

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
