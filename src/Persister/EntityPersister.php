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
use GraphAware\Neo4j\OGM\EntityManager;
use GraphAware\Neo4j\OGM\Metadata\NodeEntityMetadata;
use GraphAware\Neo4j\OGM\Util\Converters\PropertyConverterFactory;

class EntityPersister
{
    /**
     * @var \GraphAware\Neo4j\OGM\Metadata\NodeEntityMetadata
     */
    protected $classMetadata;

    /**
     * @var string
     */
    protected $className;

    /**
     * @var EntityManager
     */
    protected $entityManager;

    public function __construct(EntityManager $entityManager, $className, NodeEntityMetadata $classMetadata)
    {
        $this->className = $className;
        $this->classMetadata = $classMetadata;
        $this->entityManager = $entityManager;
    }

    private function getObjectPropertyValues($object) : array{
        $propertyValues = [];
        foreach ($this->classMetadata->getPropertiesMetadata() as $field => $meta) {
            $converter = PropertyConverterFactory::getConverter($meta->getPropertyAnnotationMetadata()->getType());
            $propertyValues[$field] = ($converter) ? $converter->getDbValue($meta->getValue($object))
                : $meta->getValue($object);
        }
        return $propertyValues;
    }

    public function getCreateQuery($object)
    {
        $extraLabels = [];
        $removeLabels = [];
        $propertyValues = $this->getObjectPropertyValues($object);

        foreach ($this->classMetadata->getLabeledProperties() as $labeledProperty) {
            if ($labeledProperty->isLabelSet($object)) {
                $extraLabels[] = $labeledProperty->getLabelName();
            } else {
                $removeLabels[] = $labeledProperty->getLabelName();
            }
        }

        $query = sprintf('CREATE (n:%s) SET n += {properties}', $this->classMetadata->getLabel());
        if (!empty($extraLabels)) {
            foreach ($extraLabels as $label) {
                $query .= ' SET n:'.$label;
            }
        }
        if (!empty($removeLabels)) {
            foreach ($removeLabels as $label) {
                $query .= ' REMOVE n:'.$label;
            }
        }

        $query .= ' RETURN id(n) as id';

        return Statement::create($query, ['properties' => $propertyValues], spl_object_hash($object));
    }

    public function getUpdateQuery($object)
    {
        $extraLabels = [];
        $removeLabels = [];
        $propertyValues = $this->getObjectPropertyValues($object);

        foreach ($this->classMetadata->getLabeledProperties() as $labeledProperty) {
            if ($labeledProperty->isLabelSet($object)) {
                $extraLabels[] = $labeledProperty->getLabelName();
            } else {
                $removeLabels[] = $labeledProperty->getLabelName();
            }
        }
        $id = $this->classMetadata->getIdValue($object);

        $query = 'MATCH (n) WHERE id(n) = {id} SET n += {props}';
        if (!empty($extraLabels)) {
            foreach ($extraLabels as $label) {
                $query .= ' SET n:'.$label;
            }
        }
        if (!empty($removeLabels)) {
            foreach ($removeLabels as $label) {
                $query .= ' REMOVE n:'.$label;
            }
        }

        return Statement::create($query, ['id' => $id, 'props' => $propertyValues]);
    }

    /**
     * Refreshes a managed entity.
     *
     * @param int    $id
     * @param object $entity The entity to refresh
     */
    public function refresh($id, $entity)
    {
        $label = $this->classMetadata->getLabel();
        $query = sprintf('MATCH (n:%s) WHERE id(n) = {%s} RETURN n', $label, 'id');
        $result = $this->entityManager->getDatabaseDriver()->run($query, ['id' => $id]);

        if ($result->size() > 0) {
            $node = $result->firstRecord()->nodeValue('n');
            $this->entityManager->getEntityHydrator($this->className)->refresh($node, $entity);
        }
    }

    public function getDetachDeleteQuery($object)
    {
        $query = 'MATCH (n) WHERE id(n) = {id} DETACH DELETE n';
        $id = $this->classMetadata->getIdValue($object);

        return Statement::create($query, ['id' => $id]);
    }

    public function getDeleteQuery($object)
    {
        $query = 'MATCH (n) WHERE id(n) = {id} DELETE n';
        $id = $this->classMetadata->getIdValue($object);

        return Statement::create($query, ['id' => $id]);
    }
}
