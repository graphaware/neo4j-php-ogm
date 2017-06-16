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

use GraphAware\Neo4j\OGM\Tests\Integration\Models\Base\User;

/**
 * Class RepositoryFindByTest.
 *
 * @group repository-find-by
 */
class RepositoryFindByTest extends IntegrationTestCase
{
    public function setUp()
    {
        parent::setUp();
        $this->clearDb();
    }

    public function testFindUsersByAge()
    {
        $user1 = new User('user1', 32);
        $user2 = new User('user2', 41);
        $user3 = new User('user3', 41);
        $this->persist($user1, $user2, $user3);
        $this->em->flush();
        $this->em->clear();

        $users = $this->em->getRepository(User::class)->findBy(['age' => 41]);
        $this->assertCount(2, $users);
    }

    public function testFindOnByLogin()
    {
        $this->clearDb();
        $user1 = new User('user1');
        $user2 = new User('user2');
        $this->persist($user1, $user2);
        $this->em->flush();
        $this->em->clear();

        $user = $this->em->getRepository(User::class)->findOneBy(['login' => 'user1']);
        $this->assertInstanceOf(User::class, $user);

        $this->assertNull($this->em->getRepository(User::class)->findOneBy(['login' => 'user3']));
    }

    public function testFindOneByThrowsExceptionWhenMultipleFound()
    {
        $this->clearDb();
        $user1 = new User('user1');
        $user2 = new User('user1');
        $this->persist($user1, $user2);
        $this->em->flush();
        $this->em->clear();

        $this->setExpectedException(\LogicException::class);
        $user = $this->em->getRepository(User::class)->findOneBy(['login' => 'user1']);
    }
}
