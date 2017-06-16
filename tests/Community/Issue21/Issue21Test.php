<?php

/*
 * This file is part of the GraphAware Neo4j PHP OGM package.
 *
 * (c) GraphAware Ltd <info@graphaware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace GraphAware\Neo4j\OGM\Tests\Community\Issue21;

use GraphAware\Neo4j\OGM\Tests\Integration\IntegrationTestCase;

/**
 * Class Issue21Test.
 *
 * @group issue21
 */
class Issue21Test extends IntegrationTestCase
{
    public function setUp()
    {
        parent::setUp();
        $this->clearDb();
    }

    public function testUserCreation()
    {
        $user = new TestUser('M');
        $this->em->persist($user);
        $this->em->flush();
        $this->assertGraphExist(('(u:TestUser {name:"M"})'));
        $this->assertNodesCount(1);
    }

    public function testUserCanHave1SponsoredChildCreation()
    {
        $user = new TestUser('M');
        $child = new TestUser('Z');
        $this->em->persist($child);
        $user->addSponsoredChild($child);
        $this->em->persist($user);
        $child->setSponsoredBy($user);
        $this->em->flush();
        $this->assertGraphExist(('(u:TestUser {name:"M"})-[:SPONSOR_USER]->(z:TestUser {name:"Z"})'));
        $this->em->flush();
        $this->assertNodesCount(2);
        $this->assertRelationshipsCount(1);
        $this->assertSame(spl_object_hash($user), spl_object_hash($user->getSponsoredChildren()[0]->getSponsoredBy()));
    }

    public function testUserCanAddChildAfterCommit()
    {
        $user = new TestUser('M');
        $child = new TestUser('Z');
        $this->em->persist($child);
        $user->addSponsoredChild($child);
        $this->em->persist($user);
        $child->setSponsoredBy($user);
        $this->em->flush();
        $y = new TestUser('Y');
        $user->addSponsoredChild($y);
        $this->em->persist($y);
        $this->em->flush();
        $this->assertGraphExist(('(u:TestUser {name:"M"})-[:SPONSOR_USER]->(z:TestUser {name:"Y"})'));
        $this->assertNodesCount(3);
        $this->assertRelationshipsCount(2);
        $this->em->flush();
        $this->assertNodesCount(3);
        $this->assertRelationshipsCount(2);
    }

    public function testUserWithSponsoredCanBeLoadedWithThem()
    {
        $user = new TestUser('M');
        $child = new TestUser('Z');
        $user->addSponsoredChild($child);
        $this->em->persist($user);
        $child->setSponsoredBy($user);
        $y = new TestUser('Y');
        $user->addSponsoredChild($y);
        $y->setSponsoredBy($user);
        $this->em->persist($y);
        $this->em->flush();
        $this->assertGraphExist(('(u:TestUser {name:"M"})-[:SPONSOR_USER]->(z:TestUser {name:"Y"})'));
        $this->assertNodesCount(3);
        $this->assertRelationshipsCount(2);
        $this->em->clear();

        /** @var TestUser $m */
        $m = $this->em->getRepository(TestUser::class)->findOneBy(['name' => 'M']);
        $this->assertCount(2, $m->getSponsoredChildren());
        $h = new TestUser('H');
        $m->addSponsoredChild($h);
        $h->setSponsoredBy($m);
        $this->em->flush();
        $this->assertNodesCount(4);
        $this->assertRelationshipsCount(3);
    }
}
