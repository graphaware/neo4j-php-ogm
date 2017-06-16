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
use GraphAware\Neo4j\OGM\Tests\Integration\Models\MoviesDemo\Movie;
use GraphAware\Neo4j\OGM\Tests\Integration\Models\MoviesDemo\Person;

/**
 * Class MovieDatasetTest.
 *
 * @group movies-it
 */
class MovieDatasetTest extends IntegrationTestCase
{
    public function setUp()
    {
        parent::setUp();
        $this->clearDb();
        $this->playMovies();
    }

    public function testPersonCanBeLoadWithMovies()
    {
        /** @var Person $tom */
        $tom = $this->em->getRepository(Person::class)->findOneBy(['name' => 'Tom Hanks']);
        $this->assertInstanceOf(Person::class, $tom);
        $this->assertCount(12, $tom->getMovies());

        foreach ($tom->getMovies() as $movie) {
            $this->assertTrue($movie->getActors()->contains($tom));
            $this->assertSame(spl_object_hash($tom), spl_object_hash($movie->getActor('Tom Hanks')));
        }
    }

    public function testActorNameCanBeChangedWhenRetrievedFromMovie()
    {
        /** @var Movie $castAway */
        $castAway = $this->em->getRepository(Movie::class)->findOneBy(['title' => 'Cast Away']);
        $this->assertInstanceOf(Movie::class, $castAway);
        $tom = $castAway->getActor('Tom Hanks');
        $tom->setName('Tom Hanks Modified');
        $this->em->flush();

        $this->assertGraphExist('(n:Person {name:"Tom Hanks Modified"})');
    }

    public function testMovieNameCanBeChangedWhenLoadedFromActor()
    {
        /** @var Person $tom */
        $tom = $this->em->getRepository(Person::class)->findOneBy(['name' => 'Tom Hanks']);
        $this->assertInstanceOf(Person::class, $tom);
        $cast = null;
        foreach ($tom->getMovies() as $movie) {
            if ($movie->getTitle() === 'Cast Away') {
                $cast = $movie;
            }
        }
        $this->assertInstanceOf(Movie::class, $cast);
        $cast->setTitle('Cast Away 2');
        $this->em->flush();
        $this->assertGraphExist('(m:Movie {title: "Cast Away 2"})');
    }

    /**
     * @see https://github.com/graphaware/neo4j-php-ogm/issues/56
     */
    public function testActorCanBeAddedToMovie()
    {
        $person = new Person('Johnny Depp');
        $this->em->persist($person);
        $movie = new Movie('Pirates Of The Caribbean');
        $this->em->persist($movie);
        $this->em->flush();
        $this->em->clear();

        $johnny = $this->em->getRepository(Person::class)->findOneBy(['name' => 'Johnny Depp']);
        $this->assertInstanceOf(Person::class, $johnny);

        /** @var Movie $pirates */
        $pirates = $this->em->getRepository(Movie::class)->findOneBy(['title' => 'Pirates Of The Caribbean']);
        $this->assertInstanceOf(Movie::class, $pirates);

        $pirates->getActors()->add($johnny);
        $this->em->flush();
        $this->assertGraphExist('(m:Movie {title:"Pirates Of The Caribbean"})<-[:ACTED_IN]-(p:Person {name:"Johnny Depp"})');
        $this->assertCount(1, $this->em->getRepository(Person::class)->findBy(['name' => 'Johnny Depp']));
    }

    /**
     * @see https://github.com/graphaware/neo4j-php-ogm/issues/104
     * @group issue-104
     */
    public function testRelationshipReferencesCanBeRemoved()
    {
        /** @var Person $person */
        $person = $this->em->getRepository(Person::class)->findOneBy(['name' => 'Tom Hanks']);
        /** @var Movie $movie */
        $movie = $this->em->getRepository(Movie::class)->findOneBy(['title' => 'Cast Away']);

        $castFromPerson = null;
        foreach ($person->getMovies() as $m) {
            if ('Cast Away' === $m->getTitle()) {
                $castFromPerson = $m;
            }
        }
        $this->assertNotNull($castFromPerson);
        $c = count($person->getMovies());
        $this->assertEquals(spl_object_hash($castFromPerson), spl_object_hash($movie));
        $person->getMovies()->removeElement($movie);
        $movie->getActors()->removeElement($person);
        $this->assertEquals($c-1, count($person->getMovies()));
        $this->em->flush();

        $this->assertGraphNotExist('(p:Person {name:"Tom Hanks"})-[:ACTED_IN]->(m:Movie {title:"Cast Away"})');
    }

    public function testRelationshipReferencesCanBeRemovedTwice()
    {
        /** @var Person $person */
        $person = $this->em->getRepository(Person::class)->findOneBy(['name' => 'Tom Hanks']);
        /** @var Movie $movie */
        $movie = $this->em->getRepository(Movie::class)->findOneBy(['title' => 'Cast Away']);

        $castFromPerson = null;
        foreach ($person->getMovies() as $m) {
            if ('Cast Away' === $m->getTitle()) {
                $castFromPerson = $m;
            }
        }
        $this->assertNotNull($castFromPerson);
        $c = count($person->getMovies());
        $this->assertEquals(spl_object_hash($castFromPerson), spl_object_hash($movie));
        $person->getMovies()->removeElement($movie);
        $movie->getActors()->removeElement($person);
        $this->assertEquals($c-1, count($person->getMovies()));
        $this->em->flush();
        $this->em->flush();

        $this->assertGraphNotExist('(p:Person {name:"Tom Hanks"})-[:ACTED_IN]->(m:Movie {title:"Cast Away"})');
    }

    public function testRelationshipReferenceCanBeReAddedAndRemoved()
    {
        /** @var Person $person */
        $person = $this->em->getRepository(Person::class)->findOneBy(['name' => 'Tom Hanks']);
        /** @var Movie $movie */
        $movie = $this->em->getRepository(Movie::class)->findOneBy(['title' => 'Cast Away']);

        $castFromPerson = null;
        foreach ($person->getMovies() as $m) {
            if ('Cast Away' === $m->getTitle()) {
                $castFromPerson = $m;
            }
        }
        $this->assertNotNull($castFromPerson);
        $c = count($person->getMovies());
        $this->assertEquals(spl_object_hash($castFromPerson), spl_object_hash($movie));
        $person->getMovies()->removeElement($movie);
        $movie->getActors()->removeElement($person);
        $this->assertEquals($c-1, count($person->getMovies()));
        $this->em->flush();
        $this->assertGraphNotExist('(p:Person {name:"Tom Hanks"})-[:ACTED_IN]->(m:Movie {title:"Cast Away"})');
        $person->getMovies()->add($movie);
        $movie->getActors()->add($person);
        $this->em->flush();
        $this->assertGraphExist('(p:Person {name:"Tom Hanks"})-[:ACTED_IN]->(m:Movie {title:"Cast Away"})');
        $person->getMovies()->removeElement($movie);
        $movie->getActors()->removeElement($person);
        $this->em->flush();
        $this->assertGraphNotExist('(p:Person {name:"Tom Hanks"})-[:ACTED_IN]->(m:Movie {title:"Cast Away"})');
    }

    public function testRelationshipReferenceCanBeRemovedAfterNewCreation()
    {
        /** @var Person $person */
        $person = $this->em->getRepository(Person::class)->findOneBy(['name' => 'Tom Hanks']);
        $movie = new Movie('Super Movie');
        $person->getMovies()->add($movie);
        $movie->getActors()->add($person);
        $person->getMovies()->first();
        $this->em->flush();
        $this->assertGraphExist('(p:Person {name:"Tom Hanks"})-[:ACTED_IN]->(m:Movie {title:"Super Movie"})');
        $person->getMovies()->removeElement($movie);
        $movie->getActors()->removeElement($person);
        $this->em->flush();
        $this->assertGraphNotExist('(p:Person {name:"Tom Hanks"})-[:ACTED_IN]->(m:Movie {title:"Super Movie"})');
    }

    public function testThatGetterShouldNotBeCalledToTriggerLazyLoading()
    {
        /** @var Person $person */
        $person = $this->em->getRepository(Person::class)->findOneBy(['name' => 'Tom Hanks']);
        $this->assertInstanceOf(LazyCollection::class, $person->movies);

        /** @var Movie $movie */
        $movie = $this->em->getRepository(Movie::class)->findOneBy(['title' => 'The Matrix']);
        $this->assertInstanceOf(LazyCollection::class, $movie->actors);
    }

    public function testDegreeOfNodeIsReturned()
    {
        $degree = $this->client->run('MATCH (n:Person {name:"Tom Hanks"}) RETURN size((n)-[:ACTED_IN]->()) AS c')->firstRecord()->get('c');
        /** @var Person $person */
        $person = $this->em->getRepository(Person::class)->findOneBy(['name' => 'Tom Hanks']);
        $this->assertEquals($degree, $person->getMovies()->count());


    }
}
