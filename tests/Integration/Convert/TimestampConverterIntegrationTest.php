<?php

namespace GraphAware\Neo4j\OGM\Tests\Integration\Convert;

use GraphAware\Neo4j\OGM\Tests\Integration\IntegrationTestCase;
use GraphAware\Neo4j\OGM\Annotations as OGM;
use GraphAware\Neo4j\OGM\Tests\Integration\Models\SimpleRelationshipEntity\Guest;
use GraphAware\Neo4j\OGM\Tests\Integration\Models\SimpleRelationshipEntity\Hotel;
use GraphAware\Neo4j\OGM\Tests\Integration\Models\SimpleRelationshipEntity\Rating;

/**
 *
 * @group property-converter
 */
class TimestampConverterIntegrationTest extends IntegrationTestCase
{
    public function setUp()
    {
        parent::setUp();
        $this->clearDb();
    }

    public function testEntityCreationWithNullValueForTime()
    {
        $e = new TimestampConverterEntity();
        $this->persist($e);
        $this->em->flush();
        $this->assertGraphExist('(n:Entity)');
        $this->assertGraphNotExist('(n:Entity {time:null})');
    }

    /**
     * @group property-converter-it
     */
    public function testEntityWithDateTimeIsPersistedWithTimestampLong()
    {
        $e = new TimestampConverterEntity();
        $dt = new \DateTime("NOW");
        $e->setTime($dt);
        $this->persist($e);
        $this->em->flush();
        $ts = $dt->getTimestamp() * 1000;
        $this->assertGraphExist(sprintf('(n:Entity {time:%d})', $ts));
    }

    public function testEntityWithDateTimeIsRetrievedFromDatabase()
    {
        $e = new TimestampConverterEntity();
        $dt = new \DateTime("NOW");
        $e->setTime($dt);
        $this->persist($e);
        $this->em->flush();
        $ts = $dt->getTimestamp() * 1000;
        $this->em->clear();

        $o = $this->em->getRepository(TimestampConverterEntity::class)->findOneBy(['time' => $ts]);
        $this->assertInstanceOf(\DateTime::class, $o->getTime());
    }
    
    public function testTimestampsMillisAreConverted()
    {
        $dt = new \DateTime("NOW");
        $ts = $dt->getTimestamp();
        $time = (($ts*1000) + 123);
        $this->client->run('CREATE (n:Entity) SET n.time = '.$time );
        /** @var TimestampConverterEntity[] $objects */
        $objects = $this->em->getRepository(TimestampConverterEntity::class)->findAll();
        $this->assertCount(1, $objects);
        $this->assertInstanceOf(\DateTime::class, $objects[0]->getTime());
        $this->assertEquals($ts, $objects[0]->getTime()->getTimestamp());
    }

    public function testTimestampCanBeUpdated()
    {
        $e = new TimestampConverterEntity();
        $dt = new \DateTime("NOW");
        $e->setTime($dt);
        $this->persist($e);
        $this->em->flush();
        $ts = $dt->getTimestamp() * 1000;
        $this->assertGraphExist('(e:Entity {time: '.$ts.'})');

        $dt = new \DateTime("1990-01-01");
        $ts = $dt->getTimestamp() * 1000;
        $e->setTime($dt);
        $this->em->flush();
        $this->assertGraphExist('(e:Entity {time: '.$ts.'})');

    }

    public function testTimestampOnRelationshipEntityIsConverted()
    {
        $guest = new Guest('test');
        $hotel = new Hotel('Hayatt');
        $rating = new Rating($guest, $hotel, 3);
        $guest->setRating($rating);
        $hotel->setRating($rating);
        $this->em->persist($guest);
        $this->em->flush();
        $t = $rating->getCreated()->getTimestamp();
        $this->assertGraphExist('(n:Guest)-[:RATED {created: ' . ($t*1000).'}]->(h:Hotel)');
        $this->em->clear();

        /** @var Guest $guest */
        $guest = $this->em->getRepository(Guest::class)->findAll()[0];
        $this->assertEquals($t, $guest->getRating()->getCreated()->getTimestamp());
    }


}

/**
 * Class TimestampConverterEntity
 * @package GraphAware\Neo4j\OGM\Tests\Integration\Convert
 *
 * @OGM\Node(label="Entity")
 */
class TimestampConverterEntity
{
    /**
     * @var int
     *
     * @OGM\GraphId()
     */
    protected $id;

    /**
     * @var \DateTime
     *
     * @OGM\Property()
     * @OGM\Convert(type="datetime", options={"format":"long_timestamp"})
     */
    protected $time;

    /**
     * @return mixed
     */
    public function getTime()
    {
        return $this->time;
    }

    /**
     * @param mixed $time
     */
    public function setTime($time)
    {
        $this->time = $time;
    }
}