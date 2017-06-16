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

use GraphAware\Neo4j\OGM\Proxy\EntityProxy;
use GraphAware\Neo4j\OGM\Tests\Integration\Models\EntityWithSimpleRelationship\Car;
use GraphAware\Neo4j\OGM\Tests\Integration\Models\EntityWithSimpleRelationship\ModelNumber;
use GraphAware\Neo4j\OGM\Tests\Integration\Models\EntityWithSimpleRelationship\Person;

/**
 * Class EntityWithSimpleRelationshipTest.
 *
 * @group entity-simple-rel
 */
class EntityWithSimpleRelationshipTest extends IntegrationTestCase
{
    public function setUp()
    {
        parent::setUp();
        $this->clearDb();
    }

    public function testPersonIsCreated()
    {
        $person = new Person('Mike');
        $this->em->persist($person);
        $this->em->flush();

        $result = $this->client->run('MATCH (n:Person) RETURN n');
        $this->assertSame(1, $result->size());
    }

    public function testPersonIsCreatedWithCar()
    {
        $person = new Person('Mike');
        $car = new Car('Bugatti', $person);
        $person->setCar($car);
        $this->em->persist($person);
        $this->em->flush();

        $result = $this->client->run('MATCH (n:Person {name:"Mike"})-[:OWNS]->(c:Car {model:"Bugatti"}) RETURN n, c');
        $this->assertSame(1, $result->size());
    }

    public function testPersonWithCarCanBeUpdated()
    {
        $person = new Person('Mike');
        $car = new Car('Bugatti', $person);
        $person->setCar($car);
        $this->em->persist($person);
        $this->em->flush();

        $person->setName('Mike2');
        $this->em->flush();

        $result = $this->client->run('MATCH (n:Person {name:"Mike2"})-[:OWNS]->(c:Car {model:"Bugatti"}) RETURN n, c');
        $this->assertSame(1, $result->size());
    }

    public function testPersonWithCarCanBeLoaded()
    {
        $person = new Person('Mike');
        $car = new Car('Bugatti', $person);
        $person->setCar($car);
        $this->em->persist($person);
        $this->em->flush();
        $this->em->clear();
        $result = $this->client->run('MATCH (n:Person {name:"Mike"})-[:OWNS]->(c:Car {model:"Bugatti"}) RETURN n, c');
        $this->assertSame(1, $result->size());

        $entities = $this->em->getRepository(Person::class)->findAll();
        $this->assertCount(1, $entities);

        $this->assertInstanceOf(EntityProxy::class, $entities[0]);

        /** @var Person $mike */
        $mike = $entities[0];
        $mikeCar = $mike->getCar();
        $this->assertInstanceOf(Car::class, $mikeCar);
        $owner = $mikeCar->getOwner();
        $this->assertInstanceOf(Person::class, $owner);
        $this->assertSame(spl_object_hash($mike), spl_object_hash($owner));
    }

    public function testPersonWithCarLoadedCanModifyCarModelName()
    {
        $person = new Person('Mike');
        $car = new Car('Bugatti', $person);
        $person->setCar($car);
        $this->em->persist($person);
        $this->em->flush();
        $this->em->clear();

        $entities = $this->em->getRepository(Person::class)->findAll();
        /** @var Person $mike */
        $mike = $entities[0];
        $mikeCar = $mike->getCar();
        $this->assertInstanceOf(Car::class, $mikeCar);
        $mikeCar->setModel('Maseratti');
        $this->em->flush();

        $result = $this->client->run('MATCH (n:Person)-[:OWNS]->(c:Car {model: "Maseratti"}) RETURN c');
        $this->assertSame(1, $result->size());
    }

    public function testPersonWithCarAndModelNumberIsPersisted()
    {
        $person = new Person('Mike');
        $car = new Car('Bugatti', $person);
        $person->setCar($car);
        $modelN = new ModelNumber('vroom-123');
        $car->setModelNumber($modelN);
        $this->em->persist($person);
        $this->em->flush();

        $result = $this->client->run('MATCH (n:Person {name:"Mike"})-[:OWNS]->(c:Car)-[:HAS_MODEL_NUMBER]->(m:ModelNumber {number:"vroom-123"}) RETURN n, c, m');
        $this->assertSame(1, $result->size());
    }

    public function testPersonWithCarAndModelNumberCanBeLoaded()
    {
        $person = new Person('Mike');
        $car = new Car('Bugatti', $person);
        $person->setCar($car);
        $modelN = new ModelNumber('vroom-123');
        $car->setModelNumber($modelN);
        $this->em->persist($person);
        $this->em->flush();
        $this->em->clear();

        $entities = $this->em->getRepository(Person::class)->findAll();
        /** @var Person $mike */
        $mike = $entities[0];
        $this->assertSame('Mike', $mike->getName());
        $this->assertSame('vroom-123', $mike->getCar()->getModelNumber()->getNumber());
        $this->assertSame(spl_object_hash($mike), spl_object_hash($mike->getCar()->getModelNumber()->getCarReference()->getOwner()));
    }

    /**
     * @group issue-108
     * @group issue-108-2
     */
    public function testRemoveRelationshipIsPersisted()
    {
        $person = new Person('Mike');
        $car = new Car('Bugatti', $person);
        $person->setCar($car);
        $this->em->persist($person);
        $this->em->flush();

        $result = $this->client->run('MATCH (n:Person {name:"Mike"})-[:OWNS]->(c:Car {model:"Bugatti"}) RETURN n, c');
        $this->assertSame(1, $result->size());
        //exit;

        $this->em->clear();

        /** @var Car $bugatti */
        $bugatti = $this->em->getRepository(Car::class)->findOneBy(array('model' => 'Bugatti'));
        //echo "\n|" . print_r($bugatti->getModel(),1 ) . "|\n";
        /** @var Person $mike */
        $mike = $this->em->getRepository(Person::class)->findOneBy(array('name' => 'Mike'));
        //echo "\n|" . print_r($mike->getName(),1 ) . "|\n";
        $this->assertEquals(spl_object_hash($bugatti->getOwner()), spl_object_hash($mike));

        $mike->setCar(null);
        $bugatti->setOwner(null);
        $this->em->flush();

        $result = $this->client->run('MATCH (n:Person {name:"Mike"})-[:OWNS]->(c:Car {model:"Bugatti"}) RETURN n, c');
        $this->assertSame(0, $result->size());
    }
}
