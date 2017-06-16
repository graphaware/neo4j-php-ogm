<?php

/*
 * This file is part of the GraphAware Neo4j PHP OGM package.
 *
 * (c) GraphAware Ltd <info@graphaware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace GraphAware\Neo4j\OGM;

use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\Common\Annotations\FileCacheReader;
use Doctrine\Common\EventManager;
use GraphAware\Neo4j\Client\ClientBuilder;
use GraphAware\Neo4j\Client\ClientInterface;
use GraphAware\Neo4j\OGM\Converters\Converter;
use GraphAware\Neo4j\OGM\Exception\MappingException;
use GraphAware\Neo4j\OGM\Hydrator\EntityHydrator;
use GraphAware\Neo4j\OGM\Metadata\Factory\Annotation\AnnotationGraphEntityMetadataFactory;
use GraphAware\Neo4j\OGM\Metadata\Factory\GraphEntityMetadataFactoryInterface;
use GraphAware\Neo4j\OGM\Metadata\GraphEntityMetadata;
use GraphAware\Neo4j\OGM\Metadata\NodeEntityMetadata;
use GraphAware\Neo4j\OGM\Metadata\QueryResultMapper;
use GraphAware\Neo4j\OGM\Metadata\RelationshipEntityMetadata;
use GraphAware\Neo4j\OGM\Persisters\BasicEntityPersister;
use GraphAware\Neo4j\OGM\Proxy\ProxyFactory;
use GraphAware\Neo4j\OGM\Repository\BaseRepository;
use GraphAware\Neo4j\OGM\Util\ClassUtils;

class EntityManager implements EntityManagerInterface
{
    /**
     * @var UnitOfWork
     */
    protected $uow;

    /**
     * @var ClientInterface
     */
    protected $databaseDriver;

    /**
     * @var BaseRepository[]
     */
    protected $repositories = [];

    /**
     * @var QueryResultMapper[]
     */
    protected $resultMappers = [];

    /**
     * @var GraphEntityMetadata[]|RelationshipEntityMetadata[]
     */
    protected $loadedMetadata = [];

    /**
     * @var GraphEntityMetadataFactoryInterface
     */
    protected $metadataFactory;

    /**
     * @var EventManager
     */
    protected $eventManager;

    /**
     * @var string
     */
    protected $proxyDirectory;

    /**
     * @var array
     */
    protected $proxyFactories = [];

    /**
     * @var array
     */
    protected $entityHydrators = [];

    /**
     * @var array
     */
    protected $entityPersisters = [];

    public function __construct(
        ClientInterface $databaseDriver,
        $cacheDirectory = null,
        EventManager $eventManager = null,
        GraphEntityMetadataFactoryInterface $metadataFactory = null
    ) {
        $this->eventManager = $eventManager ?: new EventManager();
        $this->uow = new UnitOfWork($this);
        $this->databaseDriver = $databaseDriver;

        if ($metadataFactory === null) {
            $reader = new FileCacheReader(new AnnotationReader(), $cacheDirectory, $debug = true);
            $metadataFactory = new AnnotationGraphEntityMetadataFactory($reader);
        }
        $this->metadataFactory = $metadataFactory;
        $this->proxyDirectory = $cacheDirectory;
    }

    /**
     * @param string            $host
     * @param string|null       $cacheDir
     * @param EventManager|null $eventManager
     *
     * @return EntityManager
     */
    public static function create($host, $cacheDir = null, EventManager $eventManager = null)
    {
        $cache = $cacheDir ?: sys_get_temp_dir();
        $client = ClientBuilder::create()
            ->addConnection('default', $host)
            ->build();

        return new self($client, $cache, $eventManager);
    }

    /**
     * {@inheritdoc}
     */
    public function find($className, $id)
    {
        return $this->getRepository($className)->findOneById($id);
    }

    /**
     * {@inheritdoc}
     */
    public function remove($object, $detachRelationships = false)
    {
        $this->uow->scheduleDelete($object, $detachRelationships);
    }

    /**
     * {@inheritdoc}
     */
    public function merge($entity)
    {
        if (!is_object($entity)) {
            throw new \Exception('EntityManager::merge() expects an object');
        }

        $this->uow->merge($entity);
    }

    /**
     * {@inheritdoc}
     */
    public function detach($entity)
    {
        if (!is_object($entity)) {
            throw new \Exception('EntityManager::detach() expects an object');
        }

        $this->uow->detach($entity);
    }

    /**
     * {@inheritdoc}
     */
    public function refresh($entity)
    {
        if (!is_object($entity)) {
            throw new \Exception('EntityManager::refresh() expects an object');
        }

        $this->uow->refresh($entity);
    }

    /**
     * {@inheritdoc}
     */
    public function getClassMetadata($className)
    {
        if (array_key_exists($className, $this->loadedMetadata)) {
            return $this->loadedMetadata[$className];
        }

        return $this->metadataFactory->create($className);
    }

    /**
     * {@inheritdoc}
     */
    public function getMetadataFactory()
    {
        return $this->metadataFactory;
    }

    public function initializeObject($obj)
    {
        $this->uow->initializeObject($obj);
    }

    public function contains($entity)
    {
        return $this->uow->isScheduledForCreate($entity)
        || $this->uow->isManaged($entity)
        && !$this->uow->isScheduledForDelete($entity);
    }

    /**
     * @return EventManager
     */
    public function getEventManager()
    {
        return $this->eventManager;
    }

    public function persist($entity)
    {
        if (!is_object($entity)) {
            throw new \Exception('EntityManager::persist() expects an object');
        }

        $this->uow->persist($entity);
    }

    public function flush()
    {
        $this->uow->flush();
    }

    /**
     * @return UnitOfWork
     */
    public function getUnitOfWork()
    {
        return $this->uow;
    }

    /**
     * @return \GraphAware\Neo4j\Client\ClientInterface
     */
    public function getDatabaseDriver()
    {
        return $this->databaseDriver;
    }

    public function getResultMappingMetadata($class)
    {
        if (!array_key_exists($class, $this->resultMappers)) {
            $this->resultMappers[$class] = $this->metadataFactory->createQueryResultMapper($class);
            foreach ($this->resultMappers[$class]->getFields() as $field) {
                if ($field->isEntity()) {
                    $targetFQDN = ClassUtils::getFullClassName($field->getTarget(), $class);
                    $field->setMetadata($this->getClassMetadataFor($targetFQDN));
                }
            }
        }

        return $this->resultMappers[$class];
    }

    /**
     * @param $class
     *
     * @return \GraphAware\Neo4j\OGM\Metadata\NodeEntityMetadata
     */
    public function getClassMetadataFor($class)
    {
        if (!array_key_exists($class, $this->loadedMetadata)) {
            $this->loadedMetadata[$class] = $this->metadataFactory->create($class);
        }

        return $this->loadedMetadata[$class];
    }

    /**
     * @param string $class
     *
     * @throws \Exception
     *
     * @return RelationshipEntityMetadata
     */
    public function getRelationshipEntityMetadata($class)
    {
        if (!array_key_exists($class, $this->loadedMetadata)) {
            $metadata = $this->metadataFactory->create($class);
            if (!$metadata instanceof RelationshipEntityMetadata) {
                // $class is not an relationship entity
                throw new MappingException(sprintf('The class "%s" was configured to be an RelationshipEntity but no @OGM\RelationshipEntity class annotation was found', $class));
            }
            $this->loadedMetadata[$class] = $metadata;
        }

        return $this->loadedMetadata[$class];
    }

    /**
     * @param string $class
     *
     * @return BaseRepository
     */
    public function getRepository($class)
    {
        $classMetadata = $this->getClassMetadataFor($class);
        if (!array_key_exists($class, $this->repositories)) {
            $repositoryClassName = $classMetadata->hasCustomRepository() ? $classMetadata->getRepositoryClass() : BaseRepository::class;
            $this->repositories[$class] = new $repositoryClassName($classMetadata, $this, $class);
        }

        return $this->repositories[$class];
    }

    /**
     * {@inheritdoc}
     */
    public function clear($objectName = null)
    {
        $this->uow = null;
        $this->uow = new UnitOfWork($this);
    }

    /**
     * @return string
     */
    public function getProxyDirectory()
    {
        return $this->proxyDirectory;
    }

    public function getAnnotationDriver()
    {
        // TODO: Implement getAnnotationDriver() method.
        trigger_error('The EntityManager::getAnnotationDriver is not yet implemented', E_USER_ERROR);
    }

    /**
     * @param NodeEntityMetadata $entityMetadata
     *
     * @return ProxyFactory
     */
    public function getProxyFactory(NodeEntityMetadata $entityMetadata)
    {
        if (!array_key_exists($entityMetadata->getClassName(), $this->proxyFactories)) {
            $this->proxyFactories[$entityMetadata->getClassName()] = new ProxyFactory($this, $entityMetadata);
        }

        return $this->proxyFactories[$entityMetadata->getClassName()];
    }

    /**
     * @param $className
     *
     * @return EntityHydrator
     */
    public function getEntityHydrator($className)
    {
        if (!array_key_exists($className, $this->entityHydrators)) {
            $this->entityHydrators[$className] = new EntityHydrator($className, $this);
        }

        return $this->entityHydrators[$className];
    }

    public function getEntityPersister($className)
    {
        return new BasicEntityPersister($className, $this->getClassMetadataFor($className), $this);
    }

    /**
     * @param string $cql
     * @return Query
     */
    public function createQuery($cql = '')
    {
        $query = new Query($this);

        if (!empty($cql)) {
            $query->setCQL($cql);
        }

        return $query;
    }

    public function registerPropertyConverter($name, $classname)
    {
        Converter::addConverter($name, $classname);
    }
}
