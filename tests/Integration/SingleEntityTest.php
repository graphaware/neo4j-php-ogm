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

use GraphAware\Neo4j\OGM\Proxy\EntityProxy;
use GraphAware\Neo4j\OGM\Tests\Integration\Model\AuthUser;
use GraphAware\Neo4j\OGM\Tests\Integration\Model\Movie;
use GraphAware\Neo4j\OGM\Tests\Integration\Model\User;
use LogicException;

/**
 * Class SingleEntityTest.
 *
 * @group single-entity
 */
class SingleEntityTest extends IntegrationTestCase
{
    public function setUp()
    {
        parent::setUp();
        $this->clearDb();
    }

    public function testEntityIsPersisted()
    {
        $user = new User('neo', 33);
        $this->em->persist($user);
        $this->em->flush();

        $query = 'MATCH (n:User {login:"neo"}) RETURN n';
        $result = $this->client->run($query);

        $this->assertCount(1, $result->records());
        $record = $result->records()[0];
        $userNode = $record->value('n');
        $this->assertEquals('neo', $userNode->value('login'));
        $this->assertEquals(33, $userNode->value('age'));
        $this->assertCount(1, $userNode->labels());
    }

    public function testEntityCanBeRetrieved()
    {
        $user = new User('neo', 33);
        $this->em->persist($user);
        $this->em->flush();

        $this->resetEm();

        $repository = $this->em->getRepository(User::class);
        /** @var User $user */
        $user = $repository->findOneBy('login', 'neo');

        $this->assertEquals('neo', $user->getLogin());
        $this->assertEquals(33, $user->getAge());
    }

    public function testNullPropertiesAreNotPersisted()
    {
        $user = new User('neo');
        $this->em->persist($user);
        $this->em->flush();

        $query = 'MATCH (n:User {login: "neo"}) RETURN n';
        $result = $this->client->run($query);
        $record = $result->records()[0];
        $userNode = $record->get('n');
        $this->assertFalse($userNode->hasValue('age'));
    }

    /**
     * @group label
     */
    public function testExtraLabelsCanBeAdded()
    {
        $user = new User('ikwattro');
        $user->setActive();
        $this->em->persist($user);
        $this->em->flush();
        $this->assertGraphExist('(u:User:Active {login:"ikwattro"})');
    }

    /**
     * @group label
     */
    public function testExtraLabelsCanBeRemoved()
    {
        $user = new User('ikwattro');
        $user->setActive();
        $this->em->persist($user);
        $this->em->flush();
        $this->assertGraphExist('(u:User:Active {login:"ikwattro"})');
        $user->setInactive();
        $this->em->persist($user);
        $this->em->flush();
        $this->assertGraphNotExist('(u:User:Active {login:"ikwattro"})');
    }

    /**
     * @group label-multiple
     */
    public function testMultipleNodesWithDifferentLabelsArePersisted()
    {
        $user = new User('ikwattro');
        $user->setActive();
        $this->em->persist($user);
        $movie = new Movie('Jumanji');
        $movie->setReleased();
        $this->em->persist($movie);
        $this->em->flush();
        $this->assertGraphExist('(u:User:Active {login:"ikwattro"})');
        $this->assertGraphExist('(m:Movie:Released {title:"Jumanji"})');
    }

    /**
     * @group label
     */
    public function testExtraLabelsAreHydrated()
    {
        $user = new User('ikwattro');
        $user->setActive();
        $this->em->persist($user);
        $this->em->flush();
        $this->em->clear();
        /** @var User $ikwattro */
        $ikwattro = $this->em->getRepository(User::class)->findOneBy('login', 'ikwattro');
        $this->assertTrue($ikwattro->isActive());
    }

    /**
     * @group label
     */
    public function testExtraLabelsHydrateFalseWhenNodeDontHaveLabel()
    {
        $user = new User('ikwattro');
        $this->em->persist($user);
        $this->em->flush();
        $this->em->clear();
        /** @var User $ikwattro */
        $ikwattro = $this->em->getRepository(User::class)->findOneBy('login', 'ikwattro');
        $this->assertFalse($ikwattro->isActive());
    }

    /**
     * @group internal-id
     */
    public function testFindById()
    {
        $q = 'CREATE (n:User) RETURN n';
        $result = $this->client->run($q);
        $id = $result->firstRecord()->get('n')->identity();

        $user = $this->em->getRepository(User::class)->findOneById($id);
        $this->assertInstanceOf(User::class, $user);
        $this->assertEquals($id, $user->getId());
    }

    /**
     * @group so-issue
     */
    public function testMultiplePropertiesArePersistedOnNode()
    {
        $user = new AuthUser('ikwattro', 'password');
        $this->em->persist($user);
        $this->em->flush();

        $this->assertGraphExist('(u:User {username:"ikwattro", password:"password"})');
    }

    /**
     * @group remove
     */
    public function testEntitiesCanBeRemoved()
    {
        $this->clearDb();
        $user = new User('john');
        $this->em->persist($user);
        $this->em->flush();
        $this->assertGraphExist('(u:User {login:"john"})');
        $this->em->remove($user);
        $this->em->flush();
        $this->assertGraphNotExist('(u:User {login:"john"})');
    }

    /**
     * @group remove
     */
    public function testDeletedEntitiesCannotBePersistedAfterwards()
    {
        $this->clearDb();
        $user = new User('john');
        $this->em->persist($user);
        $this->em->flush();
        $this->assertGraphExist('(u:User {login:"john"})');
        $this->em->remove($user);
        $this->em->flush();
        $this->assertGraphNotExist('(u:User {login:"john"})');
        $this->setExpectedException(LogicException::class);
        $this->em->persist($user);
    }

    public function testFindEntitiesWithFindAll()
    {
        $this->clearDb();
        $this->playMovies();
        /** @var Movie[] $movies */
        $movies = $this->em->getRepository(Movie::class)->findAll();
        $this->assertCount(38, $movies);
        $this->assertInstanceOf(EntityProxy::class, $movies[0]->getActors()[0]);
    }


}
