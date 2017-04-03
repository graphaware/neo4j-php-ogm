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

use GraphAware\Neo4j\Client\Stack;
use GraphAware\Neo4j\OGM\EntityManager;
use GraphAware\Neo4j\OGM\Metadata\LabeledPropertyMetadata;
use GraphAware\Neo4j\OGM\Util\Converters\PropertyConverterFactory;

class FlushOperationProcessor
{
    const TAG_NODES_CREATE = 'ogm_uow_nodes_create';

    private $em;

    public function __construct(EntityManager $entityManager)
    {
        $this->em = $entityManager;
    }

    public function processNodesCreationJob(array $nodesScheduledForCreate)
    {
        $byLabelsMap = [];
        foreach ($nodesScheduledForCreate as $node) {
            $metadata = $this->em->getClassMetadataFor(get_class($node));
            $byLabelsMap[$metadata->getLabel()][] = $node;
        }

        return $this->createLabeledNodesCreationStack($byLabelsMap);
    }

    private function createLabeledNodesCreationStack(array $byLabelsMap)
    {
        $stack = Stack::create(self::TAG_NODES_CREATE);
        $statements = [];
        foreach ($byLabelsMap as $label => $entities) {
            foreach ($entities as $entity) {
                $query = sprintf('UNWIND {nodes} as node
                CREATE (n:`%s`) SET n += node.props', $label);
                $metadata = $this->em->getClassMetadataFor(get_class($entity));
                $oid = spl_object_hash($entity);
                $labeledProperties = $metadata->getLabeledPropertiesToBeSet($entity);
                $lblKey = sprintf('%s_%s', $metadata->getLabel(), implode('_', array_map(function (LabeledPropertyMetadata $labeledPropertyMetadata) {
                    return $labeledPropertyMetadata->getLabelName();
                }, $labeledProperties)));

                foreach ($labeledProperties as $labeledPropertyMetadata) {
                    $query .= sprintf(' SET n:`%s`', $labeledPropertyMetadata->getLabelName());
                }

                $query .= ' RETURN id(n) as id, node.oid as oid';
                $statements[$lblKey]['query'] = $query;
                $convertedPropertyValues = [];
                foreach($metadata->getPropertyValuesArray($entity) as $key => $prop)
                {
                    $converter = PropertyConverterFactory::getConverter(
                        $metadata->getPropertyMetadata($key)->getPropertyAnnotationMetadata()->getType()
                    );
                    $convertedPropertyValues[$key] = $converter ? $converter->getDbValue($prop) : $prop;
                }
                $statements[$lblKey]['nodes'][] = [
                    'props' => $convertedPropertyValues,
                    'oid' => $oid,
                ];
            }
        }

        foreach ($statements as $key => $statement) {
            $stack->push($statement['query'], ['nodes' => $statement['nodes']], $key);
        }

        return $stack;
    }
}
