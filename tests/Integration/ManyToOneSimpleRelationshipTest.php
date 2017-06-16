<?php

namespace GraphAware\Neo4j\OGM\Tests\Integration;

use GraphAware\Neo4j\OGM\Tests\Integration\Models\ManyToOne\Bag;
use GraphAware\Neo4j\OGM\Tests\Integration\Models\ManyToOne\Woman;

/**
 * Class ManyToOneSimpleRelationshipTest
 * @package GraphAware\Neo4j\OGM\Tests\Integration
 *
 * @group many-to-one-simple
 * @group issue-126
 */
class ManyToOneSimpleRelationshipTest extends IntegrationTestCase
{
    public function setUp()
    {
        parent::setUp();
        $this->clearDb();
    }

    public function testWomanWithManyBagsCanBePersisted()
    {
        $this->createWomanWithBags();

        foreach (['a', 'b', 'c'] as $b) {
            $this->assertGraphExist(sprintf('(w:Woman)-[:OWNS_BAG]->(b:Bag {brand: "brand %s"})', $b));
        }
    }

    public function testWomanWithManyBagsCanBeRetrieved()
    {
        $this->createWomanWithBags();
        $this->em->clear();

        /** @var Woman $woman */
        $woman = $this->em->getRepository(Woman::class)->findOneBy(['name' => 'giulia']);
        $this->assertCount(3, $woman->getBags());
        $this->assertInstanceOf(Bag::class, $woman->getBags()[0]);
    }

    /**
     * Note that in theory, a woman could not separate a bag from herself.
     * I couldn't find any Exception class to match with expectedException though.
     *
     * @group issue-126-test
     */
    public function testWomanCanRemoveBag()
    {
        $this->createWomanWithBags();
        $this->em->clear();
        /** @var Woman $woman */
        $woman = $this->em->getRepository(Woman::class)->findOneBy(['name' => 'giulia']);
        $this->assertCount(3, $woman->getBags());
        /** @var Bag $bagToRemove */
        $bagToRemove = $woman->getBags()[0];
        $hash = spl_object_hash($bagToRemove);
        $woman->getBags()->removeElement($bagToRemove);
        $this->assertCount(2, $woman->getBags());
        $bagToRemove->setOwner(null);
        $this->em->flush();
        $this->assertCount(2, $woman->getBags());

        $this->assertGraphNotExist(sprintf('(w:Woman)-[:OWNS_BAG]->(b:Bag {brand: "%s"})', $bagToRemove->getBrand()));
        $result = $this->client->run('MATCH (n:Woman)-[:OWNS_BAG]->(b) RETURN n, count(b) AS c');
        $this->assertEquals(2, $result->firstRecord()->get('c'));
    }

    private function createWomanWithBags()
    {
        $woman = new Woman("giulia");
        foreach (['a', 'b', 'c'] as $b) {
            $bag = new Bag();
            $bag->setBrand(sprintf('brand %s', $b));
            $bag->setOwner($woman);
            $woman->getBags()->add($bag);
        }

        $this->persist($woman);
        $this->em->flush();
    }
}