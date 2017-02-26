<?php

namespace GraphAware\Neo4j\OGM\Tests\Integration;

use GraphAware\Neo4j\OGM\Proxy\EntityProxy;
use GraphAware\Neo4j\OGM\Tests\Integration\Models\EntityWithSimpleRelationship\Car;
use GraphAware\Neo4j\OGM\Tests\Integration\Models\EntityWithSimpleRelationship\ModelNumber;
use GraphAware\Neo4j\OGM\Tests\Integration\Models\EntityWithSimpleRelationship\Person;

/**
 * Class EntityWithSimpleRelationshipTest.
 *
 * @package GraphAware\Neo4j\OGM\Tests\Integration
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
        $this->assertEquals(1, $result->size());
    }

    public function testPersonIsCreatedWithCar()
    {
        $person = new Person('Mike');
        $car = new Car('Bugatti', $person);
        $person->setCar($car);
        $this->em->persist($person);
        $this->em->flush();

        $result = $this->client->run('MATCH (n:Person {name:"Mike"})-[:OWNS]->(c:Car {model:"Bugatti"}) RETURN n, c');
        $this->assertEquals(1, $result->size());
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
        $this->assertEquals(1, $result->size());
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
        $this->assertEquals(1, $result->size());

        $entities = $this->em->getRepository(Person::class)->findAll();
        $this->assertCount(1, $entities);

        $this->assertInstanceOf(EntityProxy::class, $entities[0]);

        /** @var Person $mike */
        $mike = $entities[0];
        $mikeCar = $mike->getCar();
        $this->assertInstanceOf(Car::class, $mikeCar);
        $owner = $mikeCar->getOwner();
        $this->assertInstanceOf(Person::class, $owner);
        $this->assertEquals(spl_object_hash($mike), spl_object_hash($owner));
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
        $this->assertEquals(1, $result->size());
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
        $this->assertEquals(1, $result->size());
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
        $this->assertEquals('Mike', $mike->getName());
        $this->assertEquals('vroom-123', $mike->getCar()->getModelNumber()->getNumber());
        $this->assertEquals(spl_object_hash($mike), spl_object_hash($mike->getCar()->getModelNumber()->getCarReference()->getOwner()));
    }
}
