<?php

namespace GraphAware\Neo4j\OGM\Tests\Integration\SimpleEntity;

use GraphAware\Neo4j\OGM\Tests\Integration\IntegrationTestCase;
use GraphAware\Neo4j\OGM\Tests\Integration\SimpleEntity\Model\Human;

/**
 * Class SimpleEntityIntegrationTest
 * @package GraphAware\Neo4j\OGM\Tests\Integration\SimpleEntity
 *
 * @group simple-entity
 */
class SimpleEntityIntegrationTest extends IntegrationTestCase
{
    public function testEntityIsPersisted()
    {
        $this->clearDb();
        $entity = new Human('Thor');
        $this->em->persist($entity);
        $this->em->flush();

        $result = $this->client->run('MATCH (n:Human) RETURN n');
        $this->assertEquals(1, $result->size());

        $node = $result->firstRecord()->nodeValue('n');
        $this->assertEquals('Thor', $node->get('name'));
        $this->assertFalse($node->hasLabel('Organic'));
    }

    public function testSimpleEntityCanBeRetrieved()
    {
        $this->clearDb();
        $entity = new Human('Thor');
        $this->em->persist($entity);
        $this->em->flush();
        $this->em->clear();

        $humans = $this->em->getRepository(Human::class)->findAll();
        $this->assertEquals(1, count($humans));

    }
}