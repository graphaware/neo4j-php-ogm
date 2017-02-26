<?php

namespace GraphAware\Neo4j\OGM\Tests\Integration;

use GraphAware\Neo4j\OGM\Tests\Integration\Models\SingleEntity\User;

/**
 * @group single-entity
 */
class SingleEntityIntegrationTest extends IntegrationTestCase
{
    public function testSingleUserEntityIsCreated()
    {
        $this->clearDb();
        $user = new User('jexp');
        $this->em->persist($user);
        $this->em->flush();

        $result = $this->client->run('MATCH (n:User {login : {login} }) RETURN n', ['login' => 'jexp']);
        $this->assertEquals(1, $result->size());
        $this->assertEquals('jexp', $result->firstRecord()->get('n')->value('login'));
    }

    public function testSingleEntityFindAll()
    {
        $this->clearDb();
        $user = new User('jexp');
        $this->em->persist($user);
        $this->em->flush();
        $this->em->clear();

        $entities = $this->em->getRepository(User::class)->findAll();
        $this->assertCount(1, $entities);
    }

    public function testSingleEntityCanBeUpdated()
    {
        $this->clearDb();
        $user = new User('jexp');
        $this->em->persist($user);
        $this->em->flush();
        $this->em->clear();

        $entities = $this->em->getRepository(User::class)->findAll();
        $this->assertCount(1, $entities);

        /** @var User $jexp */
        $jexp = $entities[0];
        $jexp->setLogin('jexp2');
        $this->em->flush();

        $result = $this->client->run('MATCH (n:User) WHERE n.login = "jexp2" RETURN n');
        $this->assertEquals(1, $result->size());
    }
}
