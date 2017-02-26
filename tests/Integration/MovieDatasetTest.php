<?php

namespace GraphAware\Neo4j\OGM\Tests\Integration;

use GraphAware\Neo4j\OGM\Tests\Integration\Models\MoviesDemo\Movie;
use GraphAware\Neo4j\OGM\Tests\Integration\Models\MoviesDemo\Person;

/**
 * Class MovieDatasetTest.
 *
 * @package GraphAware\Neo4j\OGM\Tests\Integration
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
            $this->assertEquals(spl_object_hash($tom), spl_object_hash($movie->getActor('Tom Hanks')));
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
}
