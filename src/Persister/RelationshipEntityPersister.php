<?php

/*
 * This file is part of the GraphAware Neo4j PHP OGM package.
 *
 * (c) GraphAware Ltd <info@graphaware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace GraphAware\Neo4j\OGM\Persister;

use GraphAware\Common\Cypher\Statement;
use GraphAware\Neo4j\OGM\Converters\Converter;
use GraphAware\Neo4j\OGM\EntityManager;
use GraphAware\Neo4j\OGM\Metadata\RelationshipEntityMetadata;
use GraphAware\Neo4j\OGM\Util\ClassUtils;

class RelationshipEntityPersister
{
    protected $em;

    protected $class;

    /**
     * @var \GraphAware\Neo4j\OGM\Metadata\RelationshipEntityMetadata
     */
    protected $classMetadata;

    public function __construct(EntityManager $manager, $className, RelationshipEntityMetadata $classMetadata)
    {
        $this->em = $manager;
        $this->class = $className;
        $this->classMetadata = $classMetadata;
    }

    public function getCreateQuery($entity, $pov)
    {
        $class = ClassUtils::getFullClassName(get_class($entity), $pov);
        $relationshipEntityMetadata = $this->em->getRelationshipEntityMetadata($class);
        $startNode = $relationshipEntityMetadata->getStartNodeValue($entity);
        $startNodeId = $this->em->getClassMetadataFor(get_class($startNode))->getIdValue($startNode);
        $endNode = $relationshipEntityMetadata->getEndNodeValue($entity);
        $endNodeId = $this->em->getClassMetadataFor(get_class($endNode))->getIdValue($endNode);

        $relType = $this->classMetadata->getType();
        $parameters = [
            'a' => $startNodeId,
            'b' => $endNodeId,
            'fields' => [],
        ];

        foreach ($this->classMetadata->getPropertiesMetadata() as $propertyMetadata) {
            $v = $propertyMetadata->getValue($entity);
            $parameters['fields'][$propertyMetadata->getPropertyName()] = $v;
        }

        foreach ($this->classMetadata->getPropertiesMetadata() as $field => $meta) {
            $fieldId = $this->classMetadata->getClassName().$field;
            if ($meta->hasConverter()) {
                $converter = Converter::getConverter($meta->getConverterType(), $fieldId);
                $v = $converter->toDatabaseValue($meta->getValue($entity), $meta->getConverterOptions());
                $parameters['fields'][$field] = $v;
            } else {
                $parameters['fields'][$field] = $meta->getValue($entity);
            }
        }

        $query = 'MATCH (a), (b) WHERE id(a) = {a} AND id(b) = {b}'.PHP_EOL;
        $query .= sprintf('CREATE (a)-[r:%s]->(b)', $relType).PHP_EOL;
        if (!empty($parameters['fields'])) {
            $query .= 'SET r += {fields} ';
        }
        $query .= 'RETURN id(r) AS id, {oid} AS oid';
        $parameters['oid'] = spl_object_hash($entity);

        return Statement::create($query, $parameters);
    }

    public function getUpdateQuery($entity)
    {
        $id = $this->classMetadata->getIdValue($entity);

        $query = sprintf('MATCH ()-[rel]->() WHERE id(rel) = %d SET rel += {fields}', $id);

        $parameters = [
            'fields' => [],
        ];

        foreach ($this->classMetadata->getPropertiesMetadata() as $field => $meta) {
            $fieldId = $this->classMetadata->getClassName().$field;
            if ($meta->hasConverter()) {
                $converter = Converter::getConverter($meta->getConverterType(), $fieldId);
                $v = $converter->toDatabaseValue($meta->getValue($entity), $meta->getConverterOptions());
                $parameters['fields'][$field] = $v;
            } else {
                $parameters['fields'][$field] = $meta->getValue($entity);
            }
        }

        return Statement::create($query, $parameters);
    }

    public function getDeleteQuery($entity)
    {
        $id = $this->classMetadata->getIdValue($entity);
        $query = 'START rel=rel('.$id.') DELETE rel RETURN {oid} AS oid';
        $params = ['oid' => spl_object_hash($entity)];

        return Statement::create($query, $params);
    }
}
