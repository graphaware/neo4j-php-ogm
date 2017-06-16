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

use GraphAware\Neo4j\OGM\Proxy\LazyCollection;
use GraphAware\Neo4j\OGM\Tests\Integration\Models\OneToManyRE\Acquisition;
use GraphAware\Neo4j\OGM\Tests\Integration\Models\OneToManyRE\House;
use GraphAware\Neo4j\OGM\Tests\Integration\Models\OneToManyRE\Owner;

/**
 * Class OneToManyRelationshipEntityTest.
 *
 * @group rel-entity-o2m
 */
class OneToManyRelationshipEntityTest extends IntegrationTestCase
{
    public function setUp()
    {
        parent::setUp();
        $this->clearDb();
    }

    public function testOwnerAndMultipleAcquisitionsArePersisted()
    {
        $owner = new Owner('M');
        $house1 = new House();
        $house1->setAddress('A Street 1');
        $house2 = new House();
        $house2->setAddress('B Street 2');
        $owner->getAcquisitions()->add(new Acquisition($owner, $house1, 1969));
        $owner->getAcquisitions()->add(new Acquisition($owner, $house2, 1978));
        $this->em->persist($owner);
        $this->em->flush();
        $this->assertGraphExist('(o:Owner {name:"M"})-[r:ACQUIRED {year: 1969}]->(h:House {address: "A Street 1"})');
        $this->assertGraphExist('(o:Owner {name:"M"})-[r:ACQUIRED {year: 1978}]->(h:House {address: "B Street 2"})');
    }

    public function testOwnerWithMultipleAcquisitionsCanModifyAcquisition()
    {
        $owner = new Owner('M');
        $house1 = new House();
        $house1->setAddress('A Street 1');
        $house2 = new House();
        $house2->setAddress('B Street 2');
        $owner->getAcquisitions()->add(new Acquisition($owner, $house1, 1969));
        $owner->getAcquisitions()->add(new Acquisition($owner, $house2, 1978));
        $this->em->persist($owner);
        $this->em->flush();
        $this->assertGraphExist('(o:Owner {name:"M"})-[r:ACQUIRED {year: 1969}]->(h:House {address: "A Street 1"})');
        $this->assertGraphExist('(o:Owner {name:"M"})-[r:ACQUIRED {year: 1978}]->(h:House {address: "B Street 2"})');
        foreach ($owner->getAcquisitions() as $acquisition) {
            $acquisition->setYear(2008);
        }
        $this->em->flush();
        $this->assertGraphExist('(o:Owner {name:"M"})-[r:ACQUIRED {year: 2008}]->(h:House {address: "A Street 1"})');
        $this->assertGraphExist('(o:Owner {name:"M"})-[r:ACQUIRED {year: 2008}]->(h:House {address: "B Street 2"})');
    }

    /**
     * @group breaking
     */
    public function testOwnerCanBeLoadedWithMultipleAcquisitions()
    {
        $owner = new Owner('M');
        $house1 = new House();
        $house1->setAddress('A Street 1');
        $house2 = new House();
        $house2->setAddress('B Street 2');
        $owner->getAcquisitions()->add(new Acquisition($owner, $house1, 1969));
        $owner->getAcquisitions()->add(new Acquisition($owner, $house2, 1978));
        $this->em->persist($owner);
        $this->em->flush();
        $this->assertGraphExist('(o:Owner {name:"M"})-[r:ACQUIRED {year: 1969}]->(h:House {address: "A Street 1"})');
        $this->assertGraphExist('(o:Owner {name:"M"})-[r:ACQUIRED {year: 1978}]->(h:House {address: "B Street 2"})');
        $this->em->clear();

        /** @var Owner $m */
        $m = $this->em->getRepository(Owner::class)->findOneBy(['name' => 'M']);
        $this->assertInstanceOf(Owner::class, $m);
        $this->assertCount(2, $m->getAcquisitions());
        foreach ($m->getAcquisitions() as $acquisition) {
            $this->assertSame(spl_object_hash($m), spl_object_hash($acquisition->getHouse()->getAcquisition()->getOwner()));
        }
    }

    public function testOwnerLoadedWithMultipleAcquisitionsCanModify()
    {
        $owner = new Owner('M');
        $house1 = new House();
        $house1->setAddress('A Street 1');
        $house2 = new House();
        $house2->setAddress('B Street 2');
        $owner->getAcquisitions()->add(new Acquisition($owner, $house1, 1969));
        $owner->getAcquisitions()->add(new Acquisition($owner, $house2, 1978));
        $this->em->persist($owner);
        $this->em->flush();
        $this->assertGraphExist('(o:Owner {name:"M"})-[r:ACQUIRED {year: 1969}]->(h:House {address: "A Street 1"})');
        $this->assertGraphExist('(o:Owner {name:"M"})-[r:ACQUIRED {year: 1978}]->(h:House {address: "B Street 2"})');
        $this->em->clear();

        /** @var Owner $m */
        $m = $this->em->getRepository(Owner::class)->findOneBy(['name' => 'M']);
        $this->assertInstanceOf(Owner::class, $m);
        $this->assertCount(2, $m->getAcquisitions());
        foreach ($m->getAcquisitions() as $acquisition) {
            $acquisition->setYear(2000);
        }
        $this->em->flush();
    }

    public function testOwnerLoadedWithMultipleAcquisitionsCanRemoveOne()
    {
        $owner = new Owner('M');
        $house1 = new House();
        $house1->setAddress('A Street 1');
        $house2 = new House();
        $house2->setAddress('B Street 2');
        $owner->getAcquisitions()->add(new Acquisition($owner, $house1, 1969));
        $owner->getAcquisitions()->add(new Acquisition($owner, $house2, 1978));
        $this->em->persist($owner);
        $this->em->flush();
        $this->assertGraphExist('(o:Owner {name:"M"})-[r:ACQUIRED {year: 1969}]->(h:House {address: "A Street 1"})');
        $this->assertGraphExist('(o:Owner {name:"M"})-[r:ACQUIRED {year: 1978}]->(h:House {address: "B Street 2"})');
        $this->em->clear();

        /** @var Owner $m */
        $m = $this->em->getRepository(Owner::class)->findOneBy(['name' => 'M']);
        $this->assertInstanceOf(Owner::class, $m);
        $this->assertCount(2, $m->getAcquisitions());
        $a = $m->getAcquisitions()[0];
        $m->getAcquisitions()->removeElement($a);
        $this->em->remove($a);
        $this->em->flush();
        // Checks that a second flush is safe
        $this->em->flush();
    }

    public function testOwnerCanAddOneToCollection()
    {
        $owner = new Owner('M');
        $house1 = new House();
        $house1->setAddress('A Street 1');
        $this->persist($owner, $house1);
        $this->em->flush();
        $this->assertGraphExist('(o:Owner {name:"M"})');
        $this->assertGraphExist('(h:House {address: "A Street 1"})');
        $acquisition = new Acquisition($owner, $house1, 1981);
        $owner->getAcquisitions()->add($acquisition);
        $house1->setAcquisition($acquisition);
        $this->em->flush();
        $this->assertGraphExist('(o:Owner {name:"M"})-[r:ACQUIRED {year: 1981}]->(h:House {address: "A Street 1"})');
    }

    public function testOwnerCanAddOneToCollectionAfterLoad()
    {
        $owner = new Owner('M');
        $house1 = new House();
        $house1->setAddress('A Street 1');
        $this->persist($owner, $house1);
        $this->em->flush();
        $this->assertGraphExist('(o:Owner {name:"M"})');
        $this->assertGraphExist('(h:House {address: "A Street 1"})');
        $this->em->flush();
        $this->em->clear();

        /** @var Owner $me */
        $me = $this->em->getRepository(Owner::class)->findOneBy(['name' => 'M']);
        /** @var House $house */
        $house = $this->em->getRepository(House::class)->findOneBy(['address' => 'A Street 1']);
        $a = new Acquisition($me, $house, 1980);
        $this->assertInstanceOf(LazyCollection::class, $me->getAcquisitions());
        $me->getAcquisitions()->add($a);
        $this->em->flush();
        $this->assertGraphExist('(o:Owner {name:"M"})-[r:ACQUIRED {year: 1980}]->(h:House {address: "A Street 1"})');
        // assert second flush is safe
        $this->em->flush();
        $result = $this->client->run('MATCH (o:Owner)-[r:ACQUIRED]->(h) RETURN count(r) AS c');
        $this->assertSame(1, $result->firstRecord()->get('c'));
    }
}
