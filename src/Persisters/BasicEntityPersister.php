<?php

/*
 * This file is part of the GraphAware Neo4j PHP OGM package.
 *
 * (c) GraphAware Ltd <info@graphaware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace GraphAware\Neo4j\OGM\Persisters;

use GraphAware\Common\Cypher\Statement;
use GraphAware\Neo4j\OGM\EntityManager;
use GraphAware\Neo4j\OGM\Metadata\NodeEntityMetadata;
use GraphAware\Neo4j\OGM\Metadata\RelationshipMetadata;
use GraphAware\Neo4j\OGM\Util\DirectionUtils;

class BasicEntityPersister
{
    private $_className;

    private $_classMetadata;

    private $_em;

    public function __construct($className, NodeEntityMetadata $classMetadata, EntityManager $em)
    {
        $this->_className = $className;
        $this->_classMetadata = $classMetadata;
        $this->_em = $em;
    }

    /**
     * @param array      $criteria
     * @param array|null $orderBy
     *
     * @return object[]|array|null
     */
    public function load(array $criteria, array $orderBy = null)
    {
        $stmt = $this->getMatchCypher($criteria, $orderBy);
        $result = $this->_em->getDatabaseDriver()->run($stmt->text(), $stmt->parameters());

        if ($result->size() > 1) {
            throw new \LogicException(sprintf('Expected only 1 record, got %d', $result->size()));
        }

        $hydrator = $this->_em->getEntityHydrator($this->_className);
        $entities = $hydrator->hydrateAll($result);

        return count($entities) === 1 ? $entities[0] : null;
    }

    /**
     * @param $id
     *
     * @return object|null
     */
    public function loadOneById($id)
    {
        $stmt = $this->getMatchOneByIdCypher($id);
        $result = $this->_em->getDatabaseDriver()->run($stmt->text(), $stmt->parameters());
        $hydrator = $this->_em->getEntityHydrator($this->_className);
        $entities = $hydrator->hydrateAll($result);

        return count($entities) === 1 ? $entities[0] : null;
    }

    /**
     * @param array      $criteria
     * @param array|null $orderBy
     * @param int|null   $limit
     * @param int|null   $offset
     *
     * @return array|object[]
     */
    public function loadAll(array $criteria = [], array $orderBy = null, $limit = null, $offset = null)
    {
        $stmt = $this->getMatchCypher($criteria, $orderBy, $limit, $offset);
        $result = $this->_em->getDatabaseDriver()->run($stmt->text(), $stmt->parameters());

        $hydrator = $this->_em->getEntityHydrator($this->_className);

        return $hydrator->hydrateAll($result);
    }

    public function getSimpleRelationship($alias, $sourceEntity)
    {
        $stmt = $this->getSimpleRelationshipStatement($alias, $sourceEntity);
        $result = $this->_em->getDatabaseDriver()->run($stmt->text(), $stmt->parameters());
        $hydrator = $this->_em->getEntityHydrator($this->_className);

        $hydrator->hydrateSimpleRelationship($alias, $result, $sourceEntity);
    }

    public function getSimpleRelationshipCollection($alias, $sourceEntity)
    {
        $stmt = $this->getSimpleRelationshipCollectionStatement($alias, $sourceEntity);
        $result = $this->_em->getDatabaseDriver()->run($stmt->text(), $stmt->parameters());
        $hydrator = $this->_em->getEntityHydrator($this->_className);

        $hydrator->hydrateSimpleRelationshipCollection($alias, $result, $sourceEntity);
    }

    public function getRelationshipEntity($alias, $sourceEntity)
    {
        $stmt = $this->getRelationshipEntityStatement($alias, $sourceEntity);
        $result = $this->_em->getDatabaseDriver()->run($stmt->text(), $stmt->parameters());
        if ($result->size() > 1) {
            throw new \RuntimeException(sprintf('Expected 1 result, got %d', $result->size()));
        }
        $hydrator = $this->_em->getEntityHydrator($this->_className);

        $hydrator->hydrateRelationshipEntity($alias, $result, $sourceEntity);
    }

    public function getRelationshipEntityCollection($alias, $sourceEntity)
    {
        $stmt = $this->getRelationshipEntityStatement($alias, $sourceEntity);
        $result = $this->_em->getDatabaseDriver()->run($stmt->text(), $stmt->parameters());
        $hydrator = $this->_em->getEntityHydrator($this->_className);

        $hydrator->hydrateRelationshipEntity($alias, $result, $sourceEntity);
    }

    public function getCountForRelationship($alias, $sourceEntity)
    {
        $stmt = $this->getDegreeStatement($alias, $sourceEntity);
        $result = $this->_em->getDatabaseDriver()->run($stmt->text(), $stmt->parameters());

        return $result->firstRecord()->get($alias);
    }

    /**
     * @param $criteria
     * @param null|array $orderBy
     * @param null|int   $limit
     * @param null|int   $offset
     *
     * @return Statement
     */
    public function getMatchCypher(array $criteria = [], $orderBy = null, $limit = null, $offset = null)
    {
        $identifier = $this->_classMetadata->getEntityAlias();
        $classLabel = $this->_classMetadata->getLabel();
        $cypher = 'MATCH ('.$identifier.':'.$classLabel.') ';

        $filter_cursor = 0;
        $params = [];

        foreach ($criteria as $key => $criterion) {
            $key = (string) $key;
            $clause = $filter_cursor === 0 ? 'WHERE' : 'AND';
            $cypher .= sprintf('%s %s.%s = {%s} ', $clause, $identifier, $key, $key);
            $params[$key] = $criterion;
            ++$filter_cursor;
        }

        $cypher .= 'RETURN '.$identifier;

        if (is_array($orderBy) && count($orderBy) > 0) {
            $cypher .= PHP_EOL;
            $i = 0;
            foreach ($orderBy as $property => $order) {
                $cypher .= $i === 0 ? 'ORDER BY ' : ', ';
                $cypher .= sprintf('%s.%s %s', $identifier, $property, $order);
                ++$i;
            }
        }

        if (is_int($offset) && is_int($limit)) {
            $cypher .= PHP_EOL;
            $cypher .= sprintf('SKIP %d', $offset);
        }

        if (is_int($limit)) {
            $cypher .= PHP_EOL;
            $cypher .= sprintf('LIMIT %d', $limit);
        }

        return Statement::create($cypher, $params);
    }

    private function getSimpleRelationshipStatement($alias, $sourceEntity)
    {
        $relationshipMeta = $this->_classMetadata->getRelationship($alias);
        $relAlias = $relationshipMeta->getAlias();
        $targetAlias = $this->_em->getClassMetadataFor($relationshipMeta->getTargetEntity())->getEntityAlias();
        $sourceEntityId = $this->_classMetadata->getIdValue($sourceEntity);
        $relationshipType = $relationshipMeta->getType();

        $isIncoming = $relationshipMeta->getDirection() === DirectionUtils::INCOMING ? '<' : '';
        $isOutgoing = $relationshipMeta->getDirection() === DirectionUtils::OUTGOING ? '>' : '';

        $relPattern = sprintf('%s-[%s:`%s`]-%s', $isIncoming, $relAlias, $relationshipType, $isOutgoing);

        $cypher = 'MATCH (n) WHERE id(n) = {id} ';
        $cypher .= 'MATCH (n)'.$relPattern.'('.$targetAlias.') ';
        $cypher .= 'RETURN '.$targetAlias;

        $params = ['id' => (int) $sourceEntityId];

        return Statement::create($cypher, $params);
    }

    private function getRelationshipEntityStatement($alias, $sourceEntity)
    {
        $relationshipMeta = $this->_classMetadata->getRelationship($alias);
        $relAlias = $relationshipMeta->getAlias();
        $targetAlias = $this->_em->getClassMetadataFor($relationshipMeta->getRelationshipEntityClass())->getEntityAlias();
        $sourceEntityId = $this->_classMetadata->getIdValue($sourceEntity);
        $relationshipType = $relationshipMeta->getType();

        $isIncoming = $relationshipMeta->getDirection() === DirectionUtils::INCOMING ? '<' : '';
        $isOutgoing = $relationshipMeta->getDirection() === DirectionUtils::OUTGOING ? '>' : '';

        $target = $isIncoming ? 'startNode' : 'endNode';

        $relPattern = sprintf('%s-[%s:`%s`]-%s', $isIncoming, $relAlias, $relationshipType, $isOutgoing);

        $cypher = 'MATCH (n) WHERE id(n) = {id} ';
        $cypher .= 'MATCH (n)'.$relPattern.'('.$targetAlias.') ';
        $cypher .= 'RETURN {target: '.$target.'('.$relAlias.'), re: '.$relAlias.'} AS '.$relAlias;

        $params = ['id' => $sourceEntityId];

        return Statement::create($cypher, $params);
    }

    private function getSimpleRelationshipCollectionStatement($alias, $sourceEntity)
    {
        $relationshipMeta = $this->_classMetadata->getRelationship($alias);
        $relAlias = $relationshipMeta->getAlias();
        $targetAlias = $this->_em->getClassMetadataFor($relationshipMeta->getTargetEntity())->getEntityAlias();
        $sourceEntityId = $this->_classMetadata->getIdValue($sourceEntity);
        $relationshipType = $relationshipMeta->getType();

        $isIncoming = $relationshipMeta->getDirection() === DirectionUtils::INCOMING ? '<' : '';
        $isOutgoing = $relationshipMeta->getDirection() === DirectionUtils::OUTGOING ? '>' : '';

        $relPattern = sprintf('%s-[%s:`%s`]-%s', $isIncoming, $relAlias, $relationshipType, $isOutgoing);

        $cypher = 'MATCH (n) WHERE id(n) = {id} ';
        $cypher .= 'MATCH (n)'.$relPattern.'('.$targetAlias.') ';
        $cypher .= 'RETURN '.$targetAlias.' AS '.$targetAlias.' ';

        if ($relationshipMeta->hasOrderBy()) {
            $cypher .= 'ORDER BY '.$targetAlias.'.'.$relationshipMeta->getOrderByProperty().' '.$relationshipMeta->getOrder();
        }

        $params = ['id' => $sourceEntityId];

        return Statement::create($cypher, $params);
    }

    private function getMatchOneByIdCypher($id)
    {
        $identifier = $this->_classMetadata->getEntityAlias();
        $label = $this->_classMetadata->getLabel();
        $cypher = 'MATCH ('.$identifier.':`'.$label.'`) WHERE id('.$identifier.') = {id} RETURN '.$identifier;
        $params = ['id' => (int) $id];

        return Statement::create($cypher, $params);
    }

    private function getDegreeStatement($alias, $sourceEntity)
    {
        $relationshipMeta = $this->_classMetadata->getRelationship($alias);
        $relAlias = $relationshipMeta->getAlias();
        $sourceEntityId = $this->_classMetadata->getIdValue($sourceEntity);
        $relationshipType = $relationshipMeta->getType();

        $isIncoming = $relationshipMeta->getDirection() === DirectionUtils::INCOMING ? '<' : '';
        $isOutgoing = $relationshipMeta->getDirection() === DirectionUtils::OUTGOING ? '>' : '';

        $relPattern = sprintf('%s-[:`%s`]-%s', $isIncoming, $relationshipType, $isOutgoing);

        $cypher  = 'MATCH (n) WHERE id(n) = {id} ';
        $cypher .= 'RETURN size((n)'.$relPattern.'()) ';
        $cypher .= 'AS '.$alias;

        return Statement::create($cypher, ['id' => $sourceEntityId]);

    }
}
