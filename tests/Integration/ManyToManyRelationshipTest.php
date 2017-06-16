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

use GraphAware\Neo4j\OGM\Tests\Integration\Models\ManyToManyRelationship\Group;
use GraphAware\Neo4j\OGM\Tests\Integration\Models\ManyToManyRelationship\User;

/**
 * Class ManyToManyRelationshipTest.
 *
 * @group many-to-many-rel
 */
class ManyToManyRelationshipTest extends IntegrationTestCase
{
    public function setUp()
    {
        parent::setUp();
        $this->clearDb();
    }

    public function testUsersCanHaveGroups()
    {
        $user = new User('jim');
        $group1 = new Group('owners');
        $group2 = new Group('creators');
        $user->getGroups()->add($group1);
        $user->getGroups()->add($group2);
        $this->em->persist($user);
        $this->em->flush();

        $result = $this->client->run('MATCH (g1:Group {name:"owners"})<-[:IN_GROUP]-(u:User {login:"jim"})-[:IN_GROUP]->(g2:Group {name:"creators"}) RETURN u, g1, g2');
        $this->assertSame(1, $result->size());
    }

    public function testUserCanBeLoadedWithGroups()
    {
        $user = new User('jim');
        $group1 = new Group('owners');
        $group2 = new Group('creators');
        $user->getGroups()->add($group1);
        $user->getGroups()->add($group2);
        $this->em->persist($user);
        $this->em->flush();
        $this->em->clear();

        $entities = $this->em->getRepository(User::class)->findAll();
        /** @var User $jim */
        $jim = $entities[0];
        $this->assertSame('jim', $jim->getLogin());
        $this->assertCount(2, $jim->getGroups());
        $oid = spl_object_hash($jim);
        foreach ($jim->getGroups() as $group) {
            $this->assertSame($oid, spl_object_hash($group->getUsers()[0]));
        }
    }

    public function testUserCanHaveGroupAdded()
    {
        $user = new User('jim');
        $group1 = new Group('owners');
        $group2 = new Group('creators');
        $user->getGroups()->add($group1);
        $user->getGroups()->add($group2);
        $this->em->persist($user);
        $this->em->flush();
        $group3 = new Group('admin');
        $user->getGroups()->add($group3);
        $this->em->flush();

        $result = $this->client->run('MATCH (n:User {login:"jim"})-[:IN_GROUP]->(g) RETURN n, g');
        $this->assertSame(3, $result->size());
    }

    public function testUserCanHaveGroupAddedAfterClear()
    {
        $user = new User('jim');
        $group1 = new Group('owners');
        $group2 = new Group('creators');
        $user->getGroups()->add($group1);
        $user->getGroups()->add($group2);
        $this->em->persist($user);
        $this->em->flush();
        $this->em->clear();

        $entities = $this->em->getRepository(User::class)->findAll();
        /** @var User $jim */
        $jim = $entities[0];
        $ng = new Group('admin');
        $jim->getGroups()->add($ng);
        $this->em->flush();
        $result = $this->client->run('MATCH (n:User {login:"jim"})-[:IN_GROUP]->(g) RETURN n, g');
        $this->assertSame(3, $result->size());
    }

    public function testGroupNameCanChange()
    {
        $user = new User('jim');
        $group1 = new Group('owners');
        $group2 = new Group('creators');
        $user->getGroups()->add($group1);
        $user->getGroups()->add($group2);
        $this->em->persist($user);
        $this->em->flush();
        $this->em->clear();
        $entities = $this->em->getRepository(User::class)->findAll();
        /** @var User $jim */
        $jim = $entities[0];
        $groups = $jim->getGroups();
        foreach ($groups as $group) {
            $group->setName('newname');
        }
        $this->em->flush();
        $result = $this->client->run('MATCH (n:User {login:"jim"})-[:IN_GROUP]->(g:Group {name:"newname"}) RETURN n, g');
        $this->assertSame(2, $result->size());
    }
}
