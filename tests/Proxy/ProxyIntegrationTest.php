<?php

namespace GraphAware\Neo4j\OGM\Tests\Proxy;

use GraphAware\Neo4j\OGM\Proxy\EntityProxy;
use GraphAware\Neo4j\OGM\Tests\Integration\IntegrationTestCase;
use GraphAware\Neo4j\OGM\Tests\Proxy\Model\Group;
use GraphAware\Neo4j\OGM\Tests\Proxy\Model\User;

class ProxyIntegrationTest extends IntegrationTestCase
{
    public function setUp()
    {
        parent::setUp();
        $this->clearDb();
        $this->createGraph();
    }

    public function testProxyIsCreated()
    {
        /** @var User $user */
        $user = $this->em->getRepository(User::class)->findOneBy('login', 'ikwattro');
        $this->assertInstanceOf(EntityProxy::class, $user);
        $profile = $user->getProfile();
        $userRef = $profile->getUser();
        $this->assertEquals(spl_object_hash($user), spl_object_hash($userRef));
    }

    public function testFetchRelationsAreNotReInitialized()
    {
        /** @var User $user */
        $user = $this->em->getRepository(User::class)->findOneBy('login', 'ikwattro');
        $account = $user->getAccount();
        $userRef = $account->getUser();
        $this->assertEquals(spl_object_hash($user), spl_object_hash($userRef));
    }

    public function testProxyInDepthTwo()
    {
        $this->clearDb();
        $group = new Group();
        $user = new User('ikwattro');
        $user->getAccount()->setGroup($group);
        $this->em->persist($user);
        $user2 = new User('tim');
        $user2->getAccount()->setGroup($group);
        $this->em->persist($user2);
        $this->em->flush();
        $this->em->clear();

        /** @var User $ikwattro */
        $ikwattro = $this->em->getRepository(User::class)->findOneBy('login', 'ikwattro');
        $group = $ikwattro->getAccount()->getGroup();
        foreach ($group->getAccounts() as $account) {
            if ($account->getUser()->getLogin() === 'ikwattro') {
                $this->assertEquals(spl_object_hash($account->getUser()), spl_object_hash($ikwattro));
            }
        }
    }

    private function createGraph()
    {
        $user = new User('ikwattro');
        $this->em->persist($user);
        $this->em->flush();
        $this->em->clear();
    }
}