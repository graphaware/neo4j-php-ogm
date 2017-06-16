<?php

/*
 * This file is part of the GraphAware Neo4j PHP OGM package.
 *
 * (c) GraphAware Ltd <info@graphaware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace GraphAware\Neo4j\OGM\Tests\Proxy;

use GraphAware\Neo4j\OGM\Tests\Integration\IntegrationTestCase;

class SingleNodeInitializerTest extends IntegrationTestCase
{
    public function testProxyIsReturnedWhenCalledFromRepository()
    {
        $this->clearDb();
        $id = $this->createSmallGraph();
        $init = $this->em->getRepository(Init::class)->findOneById($id);
        $this->assertInstanceOf(Related::class, $init->getRelation());
    }

    private function createSmallGraph()
    {
        return $this->client->run('CREATE (n:Init)-[:RELATES]->(n2:Related) RETURN id(n) AS id')->firstRecord()->get('id');
    }
}
