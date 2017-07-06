<?php

/*
 * This file is part of the GraphAware Neo4j PHP OGM package.
 *
 * (c) GraphAware Ltd <info@graphaware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

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
        $this->assertSame(1, $result->size());
        $this->assertSame('jexp', $result->firstRecord()->get('n')->value('login'));
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
        $this->assertSame(1, $result->size());
    }

    public function testFindOneByIdShouldNotReturnIfNodeDoesntMatchLabel()
    {
        $this->clearDb();
        $id = $this->client->run('CREATE (n:NonUser {login:"me"}) RETURN id(n) AS id')->firstRecord()->get('id');

        $user = $this->em->getRepository(User::class)->findOneById($id);
        $this->assertNull($user);
    }

    public function testFindOneByIdReturnEntityWhenLabelMatch()
    {
        $this->clearDb();
        $id = $this->client->run('CREATE (n:User {login:"me"}) RETURN id(n) AS id')->firstRecord()->get('id');

        $user = $this->em->getRepository(User::class)->findOneById($id);
        $this->assertInstanceOf(User::class, $user);
    }
}
