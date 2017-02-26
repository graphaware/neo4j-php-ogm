<?php

namespace GraphAware\Neo4j\OGM\Tests\Integration;

use GraphAware\Neo4j\OGM\Tests\Integration\Models\Base\User;

/**
 * @group finder-filter
 */
class RepositoryFinderFilterTest extends IntegrationTestCase
{
    public function setUp()
    {
        parent::setUp();
        $this->clearDb();
    }

    public function testEntitiesAreOrderedWithFinderMethod()
    {
        for ($i = 1000; $i >= 1; --$i) {
            $user = new User($i);
            $this->em->persist($user);
        }
        $this->em->flush();
        $this->em->clear();
        $this->assertNodesCount(1000);

        $users = $this->em->getRepository(User::class)->findBy([], ['login' => 'ASC']);
        $this->assertCount(1000, $users);

        for ($i = 1; $i <= 1000; ++$i) {
            $u = $users[$i - 1];
            $this->assertEquals($i, $u->getLogin());
        }
    }

    public function testEntitiesFetchCanBeLimited()
    {
        for ($i = 1000; $i >= 1; --$i) {
            $user = new User($i);
            $this->em->persist($user);
        }
        $this->em->flush();
        $this->em->clear();
        $this->assertNodesCount(1000);

        $users = $this->em->getRepository(User::class)->findBy([], [], 10);
        $this->assertCount(10, $users);
    }

    public function testSkipAndLimit()
    {
        for ($i = 1000; $i >= 1; --$i) {
            $user = new User($i);
            $this->em->persist($user);
        }
        $this->em->flush();
        $this->em->clear();
        $this->assertNodesCount(1000);

        $users = $this->em->getRepository(User::class)->findBy([], ['login' => 'ASC'], 10, 100);
        $this->assertCount(10, $users);
        $this->assertEquals(101, $users[0]->getLogin());
        $this->assertEquals(110, $users[9]->getLogin());
    }
}