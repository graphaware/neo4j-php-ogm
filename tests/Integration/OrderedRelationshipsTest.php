<?php

namespace GraphAware\Neo4j\OGM\Tests\Integration;

use GraphAware\Neo4j\OGM\Tests\Integration\Models\OrderedRelationships\Click;
use GraphAware\Neo4j\OGM\Tests\Integration\Models\OrderedRelationships\Item;

/**
 * Class OrderedRelationshipsTest.
 *
 * @package GraphAware\Neo4j\OGM\Tests\Integration
 *
 * @group rel-order-by
 */
class OrderedRelationshipsTest extends IntegrationTestCase
{
    public function testRelationshipsAreFetchedInOrder()
    {
        $this->clearDb();
        $item = new Item();
        for ($i = 100; $i >= 1; --$i) {
            $item->getClicks()->add(new Click($i));
        }
        $this->em->persist($item);
        $this->em->flush();
        $this->em->clear();

        /** @var Item $it */
        $it = $this->em->getRepository(Item::class)->findAll()[0];

        for ($i = 1; $i <= 100; ++$i) {
            $this->assertEquals($i, $it->getClicks()[$i - 1]->getTime());
        }
    }
}
