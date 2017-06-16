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

use GraphAware\Neo4j\OGM\Tests\Integration\Models\OrderedRelationships\Click;
use GraphAware\Neo4j\OGM\Tests\Integration\Models\OrderedRelationships\Item;

/**
 * Class OrderedRelationshipsTest.
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
            $this->assertSame($i, $it->getClicks()[$i - 1]->getTime());
        }
    }
}
