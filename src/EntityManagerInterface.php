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

use Doctrine\Common\EventManager;
use Doctrine\Common\Persistence\ObjectManager;

interface EntityManagerInterface extends ObjectManager
{
    /**
     * @return EventManager
     */
    public function getEventManager();

    /**
     * @return \GraphAware\Neo4j\OGM\UnitOfWork
     */
    public function getUnitOfWork();

    /**
     * @return \GraphAware\Neo4j\Client\Client
     */
    public function getDatabaseDriver();

    /**
     * @param string $class
     *
     * @return \GraphAware\Neo4j\OGM\Metadata\QueryResultMapper
     */
    public function getResultMappingMetadata($class);

    /**
     * @param $class
     *
     * @return \GraphAware\Neo4j\OGM\Metadata\NodeEntityMetadata
     */
    public function getClassMetadataFor($class);

    /**
     * @param string $class
     *
     * @throws \Exception
     *
     * @return \GraphAware\Neo4j\OGM\Metadata\RelationshipEntityMetadata
     */
    public function getRelationshipEntityMetadata($class);

    /**
     * @param string $class
     *
     * @return \GraphAware\Neo4j\OGM\Repository\BaseRepository
     */
    public function getRepository($class);
}
