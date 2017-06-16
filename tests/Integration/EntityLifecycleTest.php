<?php

namespace GraphAware\Neo4j\OGM\Tests\Integration;

use GraphAware\Neo4j\OGM\Tests\Integration\Models\Base\User;

/**
 *
 * @group entity-lifecycle
 */
class EntityLifecycleTest extends IntegrationTestCase
{
    public function setUp()
    {
        parent::setUp();
        $this->clearDb();
    }

    public function testEntityCanBeRefreshed()
    {
        $user = new User('M');
        $this->em->persist($user);
        $this->em->flush();
        $this->client->run('MATCH (n:User) SET n.login = "Z"');

        $this->em->refresh($user);
        $this->assertEquals("Z", $user->getLogin());
    }
}