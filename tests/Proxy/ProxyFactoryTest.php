<?php

namespace GraphAware\Neo4j\OGM\Tests\Proxy;

use GraphAware\Neo4j\OGM\Proxy\ProxyFactory;
use GraphAware\Neo4j\OGM\Tests\Integration\IntegrationTestCase;
use GraphAware\Neo4j\OGM\Tests\Proxy\Model\PHP7\User;
use GraphAware\Neo4j\OGM\Tests\Util\NodeProxy;
use GraphAware\Neo4j\OGM\Proxy\EntityProxy;

/**
 * Class ProxyFactoryTest
 * @package GraphAware\Neo4j\OGM\Tests\Proxy
 *
 * @group proxy-factory
 */
class ProxyFactoryTest extends IntegrationTestCase
{
    /**
     * @group proxy-f1
     */
    public function testProxyCreation()
    {
        $cm = $this->em->getClassMetadata(Init::class);
        $factory = new ProxyFactory($this->em, $cm);
        $id = $this->createSmallGraph();
        $o = $factory->fromNode(new NodeProxy($id));

        $this->assertInstanceOf(Init::class, $o);
        $this->assertInstanceOf(EntityProxy::class, $o);
    }

    public function testProxyIsReturnedFromRepository()
    {
        $this->clearDb();
        $id = $this->createSmallGraph();

        $init = $this->em->getRepository(Init::class)->findOneById($id);
        $this->assertInstanceOf(Init::class, $init);
        $this->assertInstanceOf(EntityProxy::class, $init);
        $this->assertNotNull($init->getId());
        $this->assertEquals('Ale', $init->getName());
        $this->assertInstanceOf(Related::class, $init->getRelation());
        $this->assertEquals('Chris', $init->getRelation()->getName());
        $this->assertInstanceOf(Profile::class, $init->getProfile());
        $this->assertEquals('php@graphaware.com', $init->getProfile()->getEmail());
        $this->assertInstanceOf(Init::class, $init->getRelation()->getInit());
        $this->assertEquals(spl_object_hash($init), spl_object_hash($init->getRelation()->getInit()));
    }

    /**
     * @requires PHP 7.0
     */
    public function testPhp7ProxyCreation()
    {
        $cm = $this->em->getClassMetadata(User::class);
        $factory = new ProxyFactory($this->em, $cm);
        $id = $this->client->run('CREATE (u:User {login:"Ale"})-[:HAS_PROFILE]->(:Profile {email:"php@graphaware.com"}) RETURN id(u) AS id')->firstRecord()->get('id');
        $o = $factory->fromNode(new NodeProxy($id));

        $this->assertInstanceOf(User::class, $o);
        $this->assertInstanceOf(EntityProxy::class, $o);
    }


    private function createSmallGraph()
    {
        return $this->client->run('CREATE (n:Init {name:"Ale"})-[:RELATES]->(n2:Related {name:"Chris"}), (n)-[:HAS_PROFILE]->(:Profile {email:"php@graphaware.com"}) RETURN id(n) AS id')->firstRecord()->get('id');
    }
}
