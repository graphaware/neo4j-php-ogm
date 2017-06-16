<?php

/*
 * This file is part of the GraphAware Neo4j PHP OGM package.
 *
 * (c) GraphAware Ltd <info@graphaware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace GraphAware\Neo4j\OGM\Metadata\Factory;

use GraphAware\Neo4j\OGM\Metadata\NodeEntityMetadata;
use GraphAware\Neo4j\OGM\Metadata\QueryResultMapper;
use GraphAware\Neo4j\OGM\Metadata\RelationshipEntityMetadata;

interface GraphEntityMetadataFactoryInterface
{
    /**
     * @param string $className
     *
     * @return NodeEntityMetadata|RelationshipEntityMetadata
     */
    public function create($className);

    /**
     * @param string $className
     *
     * @return bool
     */
    public function supports($className);

    /**
     * @param string $className
     *
     * @return QueryResultMapper
     */
    public function createQueryResultMapper($className);

    /**
     * @param string $className
     *
     * @return bool
     */
    public function supportsQueryResult($className);
}
