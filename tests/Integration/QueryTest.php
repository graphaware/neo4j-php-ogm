<?php

namespace GraphAware\Neo4j\OGM\Tests\Integration;

use GraphAware\Neo4j\OGM\Exception\Result\NonUniqueResultException;
use GraphAware\Neo4j\OGM\Proxy\LazyCollection;
use GraphAware\Neo4j\OGM\Query;
use GraphAware\Neo4j\OGM\Tests\Integration\Models\MoviesDemo\Movie;
use GraphAware\Neo4j\OGM\Tests\Integration\Models\MoviesDemo\Person;
use GraphAware\Neo4j\OGM\Tests\Integration\Models\Tree\Level;

/**
 *
 * @group query-native
 */
class QueryTest extends IntegrationTestCase
{
    public function setUp()
    {
        parent::setUp();
        $this->clearDb();
        $this->createTree();
    }

    public function testCreateQueryReturnsPlainCollectionEntities()
    {
        $q = $this->em->createQuery('MATCH (n:Level) WHERE n.code = {code} MATCH (n)-[:PARENT_LEVEL*0..]->(level) RETURN level');
        $q->addEntityMapping('level', Level::class);
        $q->setParameter('code', 'l3a');

        /** @var Level[] $levels */
        $levels = $q->execute();
        $this->assertCount(4, $levels);
        $this->assertInstanceOf(Level::class, $levels[0]);
        $this->assertEquals('root', $levels[3]->getCode());
        $this->assertCount(2, $levels[3]->getChildren());
    }

    public function testCreateQueryReturnsMixedResultWhenMoreThanOneAlias()
    {
        $q = $this->em->createQuery('MATCH (root:Level {code:"root"}) MATCH (n:Level {code:"l3a"})-[:PARENT_LEVEL*0..]->(level)
        RETURN root, level');
        $q->addEntityMapping('root', Level::class);
        $q->addEntityMapping('level', Level::class);

        $result = $q->getResult();

        $this->assertArrayHasKey('root', $result[0]);
        $this->assertArrayHasKey('level', $result[0]);

        $this->assertCount(4, $result);

        foreach ($result as $row) {
            $this->assertInternalType('array', $row);
            $this->assertArrayHasKey('root', $row);
            $this->assertArrayHasKey('level', $row);
        }
    }

    public function testNonUniqueExceptionIsThrown()
    {
        $q = $this->em->createQuery('MATCH (level:Level) RETURN level');
        $q->addEntityMapping('level', Level::class);

        $this->setExpectedException(NonUniqueResultException::class);
        $result = $q->getOneResult();
    }

    public function testTooMuchResultsThrowException()
    {
        $q = $this->em->createQuery('MATCH (level:Level) RETURN level');
        $q->addEntityMapping('level', Level::class);

        $this->setExpectedException(NonUniqueResultException::class);
        $result = $q->getOneOrNullResult();
    }

    public function testNullIsReturnedWithGetOneOrNull()
    {
        $q = $this->em->createQuery('MATCH (level:Level) WHERE level.code = "NONE" RETURN level');
        $q->addEntityMapping('level', Level::class);

        $this->assertNull($q->getOneOrNullResult());
    }

    public function testCreateQueryCanMapMixedResults()
    {
        $q = $this->em->createQuery('MATCH (n:Level) WHERE n.code = "root" MATCH (n)<-[r:PARENT_LEVEL*]-(child) 
        RETURN n AS root, collect(child) AS children');

        $q->addEntityMapping('root', Level::class);
        $q->addEntityMapping('children', Level::class, Query::HYDRATE_COLLECTION);

        $result = $q->getOneResult();

        $this->assertInstanceOf(Level::class, $result['root']);
        $this->assertInternalType('array', $result['children']);

        foreach ($result['children'] as $o) {
            $this->assertInstanceOf(Level::class, $o);
        }
    }

    public function testCreateQueryCanMapScalarResult()
    {
        $q = $this->em->createQuery('MATCH (n:Level) WHERE n.code = "root" MATCH (n)<-[r:PARENT_LEVEL*]-(child) RETURN n AS root, count(*) AS total');
        $q->addEntityMapping('root', Level::class);

        $result = $q->getOneResult();
        $this->assertInstanceOf(Level::class, $result['root']);
        $this->assertEquals(7, $result['total']);
    }

    /**
     * @group native-test
     */
    public function testCreateQueryToSimulateEager()
    {
        $this->clearDb();
        $this->playMovies();

        $q = $this->em->createQuery('MATCH (n:Person) WHERE n.name = {name} 
        MATCH (n)-[:ACTED_IN]->(movie)<-[:ACTED_IN]-(coactor) 
        RETURN n, movie, coactor');

        $q->setParameter('name', 'Tom Hanks');
        $q
            ->addEntityMapping('n', Person::class)
            ->addEntityMapping('movie', Movie::class)
            ->addEntityMapping('coactor', Person::class);

        $result = $q->execute();

        /** @var Person $tom */
        $tom = $result[0]['n'];
        $this->assertInstanceOf(Person::class, $tom);
        $this->assertInstanceOf(LazyCollection::class, $tom->getMovies());
        $movie1 = $result[0]['movie'];
        $hash = spl_object_hash($movie1);
        $tomMovie = null;
        foreach ($tom->getMovies() as $movie) {
            if ($movie->getTitle() === $movie1->getTitle()) {
                $tomMovie = $movie;
            }
        }

        $this->assertNotNull($tomMovie);
        $this->assertEquals($hash, spl_object_hash($tomMovie));
        $this->assertTrue($tom->getMovies()->contains($movie1));
    }
    

    private function createTree()
    {
        /**
         * (root)
         * (root)-(l1a)  (root)-(l1b)
         * (l1a)-(l2a)   (l1a)-(l2b)
         * (l1b)-(l2c)   (l1b)-(l2d)
         * (l2c)-(l3a)
         */
        $root = new Level('root');
        $l1a = new Level('l1a');
        $l1a->setParent($root);
        $l1b = new Level('l1b');
        $l1b->setParent($root);
        $l2a = new Level('l2a');
        $l2b = new Level('l2b');
        $l2a->setParent($l1a);
        $l2b->setParent($l1a);
        $l2c = new Level('l2c');
        $l2d = new Level('l2d');
        $l2c->setParent($l1b);
        $l2d->setParent($l1b);
        $l3a = new Level('l3a');
        $l3a->setParent($l2c);
        $this->em->persist($root);
        $this->em->flush();
        $this->em->clear();
    }
}