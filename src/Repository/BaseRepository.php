<?php

/*
 * This file is part of the GraphAware Neo4j PHP OGM package.
 *
 * (c) GraphAware Ltd <info@graphaware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace GraphAware\Neo4j\OGM\Repository;

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
use GraphAware\Neo4j\OGM\Query\Pagination;
use GraphAware\Neo4j\OGM\Query\QueryResultMapping;
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

    private static $PAGINATION_FIRST_RESULT_KEY = 'first';
    private static $PAGINATION_LIMIT_RESULTS_KEY = 'max';

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
     * @param \GraphAware\Neo4j\OGM\EntityManager          $manager
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
     * @throws \GraphAware\Neo4j\Client\Exception\Neo4jException
     *
     * @return object[]
     */
    public function findAll(array $filters = [])
    {
        $pagination = $this->getPagination($filters);
        $parameters = [];
        $label = $this->classMetadata->getLabel();
        $query = sprintf('MATCH (n:%s)', $label);

        if (null !== $pagination) {
            $query .= ' WITH n ORDER BY ';
            if (null !== $pagination->getOrderBy()) {
                $query .= 'n.'.$pagination->getOrderBy()[0].' '.$pagination->getOrderBy()[1].' ';
            } else {
                $query .= 'id(n) ASC ';
            }

            $query .= ' SKIP {skip} LIMIT {limit}';

            $parameters['skip'] = $pagination->getFirst();
            $parameters['limit'] = $pagination->getMax();
        }

        /** @var RelationshipMetadata[] $associations */
        $associations = $this->classMetadata->getFetchRelationships();
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
                $orderProperty = $association->getPropertyName().'.'.$association->getOrderByPropery();
                if ($association->isRelationshipEntity()) {
                    $reMetadata = $this->entityManager->getRelationshipEntityMetadata($association->getRelationshipEntityClass());
                    $split = explode('.', $association->getOrderByPropery());
                    if (count($split) > 1) {
                        $reName = $split[0];
                        $v = $split[1];
                        if ($reMetadata->getStartNodePropertyName() === $reName || $reMetadata->getEndNodePropertyName() === $reName) {
                            $orderProperty = $association->getPropertyName().'.'.$v;
                        }
                    } else {
                        if (null !== $reMetadata->getPropertyMetadata($association->getOrderByPropery())) {
                            $orderProperty = $relid.'.'.$association->getOrderByPropery();
                        }
                    }
                }
                $query .= $relid.', '.$association->getPropertyName().' ORDER BY '.$orderProperty.' '.$association->getOrder();
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
                $query .= ', '.implode(',', $assocReturns);
            }

            $query .= ' ORDER BY ';
            if (null !== $pagination->getOrderBy()) {
                $query .= 'n.'.$pagination->getOrderBy()[0].' '.$pagination->getOrderBy()[1].' ';
            } else {
                $query .= 'id(n) ASC ';
            }

            $parameters['skip'] = $pagination->getFirst();
            $parameters['limit'] = $pagination->getMax();
        }

        $query .= PHP_EOL;
        $query .= 'RETURN n';
        if (!empty($assocReturns)) {
            $query .= ', '.implode(', ', $assocReturns);
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

        $tag = [
            'class' => self::class,
            'method' => 'findAll',
            'arguments' => $filters,
        ];

        $result = $this->entityManager->getDatabaseDriver()->run($query, $parameters, json_encode($tag));

        return $this->entityManager->getHydrator($this->className)->hydrateResultSet($result);
    }

    /**
     * @param string $key
     * @param mixed  $value
     *
     * @throws \GraphAware\Neo4j\Client\Exception\Neo4jException
     *
     * @return object[]
     */
    public function findBy($key, $value, $isId = false)
    {
        $label = $this->classMetadata->getLabel();
        $idId = $isId ? 'id(n)' : sprintf('n.%s', $key);
        $query = sprintf('MATCH (n:%s) WHERE %s = {%s}', $label, $idId, $key);
        /** @var \GraphAware\Neo4j\OGM\Metadata\RelationshipMetadata[] $associations */
        $associations = $this->classMetadata->getFetchRelationships();
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
            $query .= 'OPTIONAL MATCH (n)' . $relQueryPart . '(' . $association->getPropertyName() . ')';
            $query .= ' WITH n, ';
            $query .= implode(', ', $assocReturns);
            if (!empty($assocReturns)) {
                $query .= ', ';
            }
            $relid = 'rel_' . $relationshipIdentifier;
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


        return $this->entityManager->getHydrator($this->className)->hydrateResultSet($result);
    }

    private function hydrateFetchRelationships($instance, Record $record)
    {
        foreach ($this->classMetadata->getFetchRelationships() as $relationship) {
            $identifier = $relationship->getPropertyName();
            $otherClass = $relationship->getTargetEntity();
            $otherNodeMeta = $this->entityManager->getClassMetadata($otherClass);
            if (!$relationship->isCollection()) {
                if (null === $record->get($identifier)) {
                    continue;
                }
                $otherInstance = null !== $this->entityManager->getUnitOfWork()->getEntityById($record->get($identifier)->identity())
                    ? $this->entityManager->getUnitOfWork()->getEntityById($record->get($identifier))
                    : $this->entityManager->getProxyFactory($otherNodeMeta)->fromNode($record->get($identifier), $this->classMetadata->getMappedByFieldsForFetch());
                $this->hydrateProperties($otherInstance, $record->get($identifier), $otherNodeMeta);
                $this->entityManager->getUnitOfWork()->addManaged($otherInstance);
                $relationship->setValue($instance, $otherInstance);
                $this->setInversedAssociation($instance, $otherInstance, $relationship->getPropertyName());
                $this->entityManager->getUnitOfWork()->addManagedRelationshipReference($instance, $otherInstance, $relationship->getPropertyName(), $relationship);
            }
        }
    }

    public function setInversedAssociation($baseInstance, $otherInstance, $relationshipKey)
    {
        $class = get_class($otherInstance);
        if (false !== get_parent_class($otherInstance)) {
            $class = get_parent_class($otherInstance);
        }
        $assoc = $this->classMetadata->getRelationship($relationshipKey);
        if ($assoc->hasMappedByProperty()) {
            $mappedBy = $assoc->getMappedByProperty();
            $reflClass = $this->getReflectionClass($class);
            $property = $reflClass->getProperty($mappedBy);
            $property->setAccessible(true);
            $otherClassMetadata = $this->entityManager->getClassMetadataFor(get_class($otherInstance));
            if ($otherClassMetadata instanceof RelationshipMetadata || $otherClassMetadata instanceof RelationshipEntityMetadata) {
                return;
            }
            if ($otherClassMetadata->getRelationship($mappedBy)->isCollection()) {
                if (null === $property->getValue($otherInstance)) {
                    $mt = $otherClassMetadata->getRelationship($mappedBy);
                    $lazy = new LazyRelationshipCollection($this->entityManager, $otherInstance, $mt->getTargetEntity(), $mt, $baseInstance);
                    $property->setValue($otherInstance, $lazy);
                } else {
                    $property->getValue($otherInstance)->addInit($baseInstance);
                }
            } else {
                $property->setValue($otherInstance, $baseInstance);
            }
        }
    }

    public function paginated($first, $max, array $order = [])
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
     * @throws \Exception
     *
     * @return null|object
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

    protected function nativeQuery($query, $parameters, QueryResultMapping $resultMapping)
    {
        $parameters = null !== $parameters ? (array) $parameters : [];
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
            $results[] = $this->entityManager->getHydrator($this->className)->hydrateQueryRecord($mappingMetadata, $record);
        }

        return $resultMapping->getQueryResultType() === QueryResultMapping::RESULT_SINGLE ? $results[0] : $results;
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


}
