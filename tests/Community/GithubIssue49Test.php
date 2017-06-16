<?php

/*
 * This file is part of the GraphAware Neo4j PHP OGM package.
 *
 * (c) GraphAware Ltd <info@graphaware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace GraphAware\Neo4j\OGM\Tests\Integration\Community;

use GraphAware\Neo4j\OGM\Tests\Integration\IntegrationTestCase;
use GraphAware\Neo4j\OGM\Tests\Integration\Models\EntityWithSimpleRelationship\Car;
use GraphAware\Neo4j\OGM\Tests\Integration\Models\EntityWithSimpleRelationship\ModelNumber;
use GraphAware\Neo4j\OGM\Tests\Integration\Models\EntityWithSimpleRelationship\Person;
use GraphAware\Neo4j\OGM\UnitOfWork;

/**
 * @author Tobias Nyholm <tobias.nyholm@gmail.com>
 *
 * @group issue-49
 */
class GithubIssue49Test extends IntegrationTestCase
{
    /**
     * When the items are new we want to make sure to cascade persist to all relations.
     */
    public function testCascadePersistOnCreate()
    {
        // Clear database
        $this->clearDb();

        $person = new Person('Mike');
        $car = new Car('Bugatti', $person);
        $person->setCar($car);
        $car->setModelNumber(new ModelNumber('Foobar'));
        $this->em->persist($person);
        $this->em->flush();

        $result = $this->client->run('MATCH (c:Car {model:"Bugatti"})-[:HAS_MODEL_NUMBER]->(m:ModelNumber {number:"Foobar"}) RETURN m, c');
        $this->assertSame(1, $result->size());
    }

    /**
     * When we do a simple update on one entity we do NOT want to fetch related entities from the
     * database and persist them as well. Unless they are already in memory.
     */
    public function testNoCascadeOnExistingItems()
    {
        // Clear database
        $this->clearDb();

        $person = new Person('Mike');
        $car = new Car('Bugatti', $person);
        $person->setCar($car);
        $car->setModelNumber(new ModelNumber('Foobar'));
        $this->em->persist($person);
        $this->em->flush();

        $this->resetEm();
        $persons = $this->em->getRepository(Person::class)->findBy(['name' => 'Mike']);
        /** @var Person $person */
        $person = $persons[0];
        $person->setName('Tom');

        $uow = new UnitOfWork($this->em);
        $visited = [];
        $uow->doPersist($person, $visited);
        $this->assertCount(1, $visited);
    }
}
