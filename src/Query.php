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

use GraphAware\Common\Result\Result;
use GraphAware\Neo4j\OGM\Exception\Result\NonUniqueResultException;
use GraphAware\Neo4j\OGM\Exception\Result\NoResultException;

class Query
{
    const PARAMETER_LIST = 0;

    const PARAMETER_MAP = 1;

    const HYDRATE_COLLECTION = "HYDRATE_COLLECTION";

    const HYDRATE_SINGLE = "HYDRATE_SINGLE";

    const HYDRATE_RAW = "HYDRATE_RAW";

    const HYDRATE_MAP = "HYDRATE_MAP";

    const HYDRATE_MAP_COLLECTION = "HYDRATE_MAP_COLLECTION";

    protected $em;

    protected $cql;

    protected $parameters = [];

    protected $mappings = [];

    protected $resultMappings = [];

    /**
     * @param EntityManager $entityManager
     */
    public function __construct(EntityManager $entityManager)
    {
        $this->em = $entityManager;
    }

    /**
     * @param $cql
     * @return $this
     */
    public function setCQL($cql)
    {
        $this->cql = $cql;

        return $this;
    }

    /**
     * @param string $key
     * @param string $value
     * @param null|int $type
     * @return $this
     */
    public function setParameter($key, $value, $type = null)
    {
        $this->parameters[$key] = [$value, $type];

        return $this;
    }

    /**
     * @param string $alias
     * @param string $className
     * @param string $hydrationType
     * @return $this
     */
    public function addEntityMapping($alias, $className, $hydrationType = self::HYDRATE_SINGLE)
    {
        $this->mappings[$alias] = [$className, $hydrationType];

        return $this;
    }

    /**
     * @return array|mixed
     */
    public function getResult()
    {
        return $this->execute();
    }

    /**
     * @return array|mixed
     * @throws \Exception
     */
    public function execute()
    {
        $stmt = $this->cql;
        $parameters = $this->formatParameters();

        $result = $this->em->getDatabaseDriver()->run($stmt, $parameters);
        if ($result->size() === 0) {
            return [];
        }

        $cqlResult = $this->handleResult($result);

        return $cqlResult;
    }

    /**
     * @return array
     */
    private function formatParameters()
    {
        $params = [];
        foreach ($this->parameters as $alias => $parameter) {
            $params[$alias] = $parameter[0];
        }

        return $params;
    }

    private function handleResult(Result $result)
    {
        $queryResult = [];

        foreach ($result->records() as $record) {
            $row = [];
            $keys = $record->keys();

            foreach ($keys as $key) {

                $mode = array_key_exists($key, $this->mappings) ? $this->mappings[$key][1] : self::HYDRATE_RAW;

                if ($mode === self::HYDRATE_SINGLE) {
                    if (count($keys) === 1) {
                        $row = $this->em->getEntityHydrator($this->mappings[$key][0])->hydrateNode($record->get($key));
                    } else {
                        $row[$key] = $this->em->getEntityHydrator($this->mappings[$key][0])->hydrateNode($record->get($key));
                    }
                } elseif ($mode === self::HYDRATE_COLLECTION) {
                    $coll = [];
                    foreach ($record->get($key) as $i) {
                        $v = $this->em->getEntityHydrator($this->mappings[$key][0])->hydrateNode($i);
                        $coll[] = $v;
                    }
                    $row[$key] = $coll;
                } elseif ($mode === self::HYDRATE_MAP_COLLECTION) {
                    $row[$key] = $this->hydrateMapCollection($record->get($key));
                } elseif ($mode === self::HYDRATE_MAP) {
                    $row[$key] = $this->hydrateMap($record->get($key));
                } elseif ($mode === self::HYDRATE_RAW) {
                    $row[$key] = $record->get($key);
                }
            }

            $queryResult[] = $row;
        }

        return $queryResult;
    }

    /**
     * Maps collection of maps.
     * For cases where map is collection of another maps,
     * like in "WITH node, { otherNode.name, otherNode.fieldName} as cols RETURN {val1:value, data: collect(cols) }"
     * in that case "cols" is collection of maps and should be mapped as
     * addEntityMapping('cols', null, Query::HYDRATE_MAP_COLLECTION);
     *
     * @param $map
     * @return array
     */
    private function hydrateMapCollection($map)
    {
        $row = [];
        foreach ($map as $key => $value) {
            $row[] = $this->hydrateMap($value);
        }
        return $row;
    }

    /**
     * Hydrates array (map) to entities (if correct mappong is present).
     *
     * Entities will be mapped to values through map keys (instead of neo4j Result`s column key as in handleResult()).
     * Useful for maps like "RETURN {val1:node, data: collect(otherNode) as } as col".
     * In this example two mapping should be present:
     * addEntityMapping('col', null, Query::HYDRATE_MAP);
     * addEntityMapping('data', OtherNode::class, Query::HYDRATE_COLLECTION);
     *
     * @param $map array
     * @return array
     */
    private function hydrateMap(array $map)
    {
        $row = [];
        foreach ($map as $key => $value) {
            $row[$key] = $this->hydrateMapValue($key, $value);
        }
        return $row;
    }

    /**
     * Recursively maps array`s $key=>$value pair to Node,
     * Node collection, map, map collection or to RAW value.
     * Mapping relies on $key
     *
     * @param $key
     * @param $value
     * @return array|mixed|null|object
     */
    private function hydrateMapValue($key, $value)
    {
        $row = [];
        $mode = array_key_exists($key, $this->mappings) ? $this->mappings[$key][1] : self::HYDRATE_RAW;

        if ($mode === self::HYDRATE_SINGLE) {
            $row = $this->em->getEntityHydrator($this->mappings[$key][0])->hydrateNode($value);
        } elseif ($mode === self::HYDRATE_COLLECTION) {
            $coll = [];
            foreach ($value as $i) {
                $v = $this->em->getEntityHydrator($this->mappings[$key][0])->hydrateNode($i);
                $coll[] = $v;
            }
            $row = $coll;
        } elseif ($mode === self::HYDRATE_MAP_COLLECTION) {
            $row = $this->hydrateMapCollection($value);
        } elseif ($mode === self::HYDRATE_MAP) {
            $row = $this->hydrateMap($value);
        } elseif ($mode === self::HYDRATE_RAW) {
            $row = $value;
        }
        return $row;
    }

    /**
     * @return mixed
     */
    public function getOneOrNullResult()
    {
        $result = $this->execute();

        if (empty($result)) {
            return null;
        }

        if (count($result) > 1) {
            throw new NonUniqueResultException(sprintf('Expected 1 or null result, got %d', count($result)));
        }


        return $result;
    }

    /**
     * @return mixed
     */
    public function getOneResult()
    {
        $result = $this->execute();

        if (count($result) > 1) {
            throw new NonUniqueResultException(sprintf('Expected 1 or null result, got %d', count($result)));
        }

        if (empty($result)) {
            throw new NoResultException();
        }

        return $result[0];
    }
}