<?php

namespace GraphAware\Neo4j\OGM\Tests\Integration;

use GraphAware\Neo4j\OGM\Query;
use GraphAware\Neo4j\OGM\Tests\Integration\Models\MoviesDemo\Movie;
use GraphAware\Neo4j\OGM\Tests\Integration\Models\MoviesDemo\Person;

/**
 * @group complex-query
 */
class ComplexQueryResultTest extends IntegrationTestCase
{
    public function setUp()
    {
        parent::setUp();
        $this->clearDb();
        $this->playMovies();
    }

    public function testQueryReturningMap()
    {
        $q = $this->em->createQuery('MATCH (n:Person)-[r:ACTED_IN]->(m)
        RETURN n, {roles: r.roles, movie: m} AS actInfo LIMIT 2');

        $q->addEntityMapping('n', Person::class);
        $q->addEntityMapping('actInfo', null, Query::HYDRATE_MAP);
        $q->addEntityMapping('movie', Movie::class);

        $result = $q->getResult();
        $this->assertCount(2, $result);
        $row = $result[0];

        $this->assertInstanceOf(Person::class, $row['n']);
        $this->assertInternalType('array', $row['actInfo']);
        $this->assertInternalType('array', $row['actInfo']['roles']);
        $this->assertInstanceOf(Movie::class, $row['actInfo']['movie']);
    }

    public function testQueryReturningMapCollectionMixed()
    {
        $q = $this->em->createQuery('MATCH (n:Person)-[r:ACTED_IN]->(m) 
        WITH n, {roles: r.roles, movie: m} AS actInfo 
        RETURN n, collect(actInfo) AS actorInfos LIMIT 2');

        $q->addEntityMapping('n', Person::class);
        $q->addEntityMapping('actorInfos', null, Query::HYDRATE_MAP_COLLECTION);
        $q->addEntityMapping('movie', Movie::class);

        $result = $q->getResult();
        $this->assertCount(2, $result);
        $this->assertInternalType('array', $result[0]['actorInfos']);
        $this->assertInstanceOf(Movie::class, $result[0]['actorInfos'][0]['movie']);
        $this->assertCount(1, $result[0]['actorInfos']);
    }

    public function testQueryReturningCollectionOfEntitiesInMap()
    {
        $q = $this->em->createQuery('MATCH (n:Person)-[r:ACTED_IN]->(m) 
        RETURN n, {score: size((n)-[:ACTED_IN]->()), movies: collect(m)} AS infos LIMIT 10');

        $q->addEntityMapping('n', Person::class);
        $q->addEntityMapping('infos', null, Query::HYDRATE_MAP);
        $q->addEntityMapping('movies', Movie::class, Query::HYDRATE_COLLECTION);

        $result = $q->getResult();

        $this->assertCount(10, $result);
        $row = $result[0];
        $this->assertInstanceOf(Person::class, $row['n']);
        $this->assertInternalType('array', $row['infos']);
        $this->assertEquals($row['infos']['score'], count($row['infos']['movies']));
        $this->assertInstanceOf(Movie::class, $row['infos']['movies'][0]);
    }

    public function testQueryReturningMapAsOnlyColumn()
    {
        $q = $this->em->createQuery('MATCH (n:Person)-[r:ACTED_IN]->(m) 
        RETURN {user: n, score: size((n)-[:ACTED_IN]->()), movies: collect(m)} AS infos LIMIT 10');

        $q->addEntityMapping('user', Person::class);
        $q->addEntityMapping('infos', null, Query::HYDRATE_MAP);
        $q->addEntityMapping('movies', Movie::class, Query::HYDRATE_COLLECTION);

        $result = $q->execute();

        $this->assertCount(10, $result);
        $row = $result[0];
        $this->assertInternalType('array', $row['infos']);
        $this->assertEquals(1, count(array_keys($row)));
        $this->assertInstanceOf(Person::class, $row['infos']['user']);
        $this->assertEquals($row['infos']['score'], count($row['infos']['movies']));
        $this->assertInstanceOf(Movie::class, $row['infos']['movies'][0]);
    }
}