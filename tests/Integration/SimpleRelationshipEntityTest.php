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

use GraphAware\Neo4j\OGM\Tests\Integration\Models\SimpleRelationshipEntity\Guest;
use GraphAware\Neo4j\OGM\Tests\Integration\Models\SimpleRelationshipEntity\Hotel;
use GraphAware\Neo4j\OGM\Tests\Integration\Models\SimpleRelationshipEntity\Rating;

/**
 * Class SimpleRelationshipEntityTest.
 *
 * @group simple-re
 */
class SimpleRelationshipEntityTest extends IntegrationTestCase
{
    public function setUp()
    {
        parent::setUp();
        $this->clearDb();
    }

    /**
     * @group simple-re-1
     */
    public function testRatingCanBetweenGuestAndHotelIsCreated()
    {
        $guest = new Guest('john');
        $hotel = new Hotel('Crowne');
        $rating = new Rating($guest, $hotel, 3.5);
        $guest->setRating($rating);
        $this->em->persist($guest);
        $this->em->persist($rating);
        $this->em->flush();
        $this->assertGraphExist('(g:Guest {name:"john"})-[:RATED {score: 3.5}]->(h:Hotel {name:"Crowne"})');
        $this->assertNotNull($rating->getId());
    }

    public function testRatingCanBeAddedOnManagedEntities()
    {
        $guest = new Guest('john');
        $hotel = new Hotel('Crowne');
        $this->persist($guest, $hotel);
        $this->em->flush();
        $this->em->clear();

        /** @var Guest $john */
        $john = $this->em->getRepository(Guest::class)->findOneBy(['name' => 'john']);
        /** @var Hotel $crowne */
        $crowne = $this->em->getRepository(Hotel::class)->findOneBy(['name' => 'Crowne']);

        $rating = new Rating($john, $crowne, 4.8);
        $john->setRating($rating);
        $this->em->flush();
        $this->assertGraphExist('(g:Guest {name:"john"})-[:RATED {score: 4.8}]->(h:Hotel {name:"Crowne"})');
    }

    public function testRatingCanBeRemoved()
    {
        $guest = new Guest('john');
        $hotel = new Hotel('Crowne');
        $rating = new Rating($guest, $hotel, 3.5);
        $guest->setRating($rating);
        $this->em->persist($guest);
        $this->em->flush();
        $this->assertGraphExist('(g:Guest {name:"john"})-[:RATED {score: 3.5}]->(h:Hotel {name:"Crowne"})');
        $guest->setRating(null);
        $hotel->setRating(null);
        $this->em->remove($rating);
        $this->em->flush();
        $this->assertGraphNotExist('(g:Guest {name:"john"})-[:RATED {score: 3.5}]->(h:Hotel {name:"Crowne"})');
        $guest->setName('John');
        $this->em->flush();
        $this->assertGraphExist('(g:Guest {name:"John"})');
        $this->assertGraphNotExist('(g:Guest)-[:RATED]->(x)');
    }

    public function testRatingCanBeRemovedAfterLoad()
    {
        $guest = new Guest('john');
        $hotel = new Hotel('Crowne');
        $rating = new Rating($guest, $hotel, 3.5);
        $guest->setRating($rating);
        $this->em->persist($guest);
        $this->em->flush();
        $this->assertGraphExist('(g:Guest {name:"john"})-[:RATED {score: 3.5}]->(h:Hotel {name:"Crowne"})');
        $this->em->clear();

        $john = $this->em->getRepository(Guest::class)->findOneBy(['name' => 'john']);
        /** @var Hotel $crowne */
        $crowne = $this->em->getRepository(Hotel::class)->findOneBy(['name' => 'Crowne']);
        $this->assertSame(spl_object_hash($john), spl_object_hash($crowne->getRating()->getGuest()));
        $this->em->remove($crowne->getRating());
        $john->setRating(null);
        $crowne->setRating(null);
        $this->em->flush();
        $this->assertGraphNotExist('(g:Guest {name:"john"})-[:RATED]->(h:Hotel)');
    }

    /**
     * @group simple-re-load
     */
    public function testRatingCanBeLoaded()
    {
        $this->client->run('CREATE (g:Guest {name:"john"})-[:RATED {score: 3.5}]->(h:Hotel {name:"Crowne"})');
        /** @var Guest $guest */
        $guest = $this->em->getRepository(Guest::class)->findOneBy(['name' => 'john']);
        $this->assertInstanceOf(Guest::class, $guest);
        $this->assertInstanceOf(Rating::class, $guest->getRating());
        $this->assertInstanceOf(Hotel::class, $guest->getRating()->getHotel());
        $this->assertSame(3.5, $guest->getRating()->getScore());
        $this->assertSame(spl_object_hash($guest), spl_object_hash($guest->getRating()->getHotel()->getRating()->getGuest()));
    }

    /**
     * @group simple-rel-upd
     */
    public function testRatingPropertyCanBeModified()
    {
        $guest = new Guest('john');
        $hotel = new Hotel('Crowne');
        $rating = new Rating($guest, $hotel, 3.5);
        $guest->setRating($rating);
        $this->em->persist($guest);
        $this->em->flush();
        $this->assertGraphExist('(g:Guest {name:"john"})-[:RATED {score: 3.5}]->(h:Hotel {name:"Crowne"})');
        $rating->setScore(5.0);
        $this->em->flush();
        $this->assertGraphExist('(g:Guest {name:"john"})-[:RATED {score: 5.0}]->(h:Hotel {name:"Crowne"})');
    }

    /**
     * @group simple-rel-upd
     */
    public function testRatingPropertyCanBeModifiedAfterLoad()
    {
        $guest = new Guest('john');
        $hotel = new Hotel('Crowne');
        $rating = new Rating($guest, $hotel, 3.5);
        $guest->setRating($rating);
        $this->em->persist($guest);
        $this->em->flush();
        $this->em->clear();

        /** @var Guest $john */
        $john = $this->em->getRepository(Guest::class)->findOneBy(['name' => 'john']);
        $john->getRating()->setScore(1.2);
        $this->em->flush();
        $this->assertGraphExist('(g:Guest {name:"john"})-[:RATED {score: 1.2}]->(h:Hotel {name:"Crowne"})');
    }

    public function testStartSideOfRelationshipEntityCanBeUpdated()
    {
        $guest = new Guest('john');
        $hotel = new Hotel('Crowne');
        $rating = new Rating($guest, $hotel, 3.5);
        $guest->setRating($rating);
        $this->em->persist($guest);
        $this->em->flush();
        $this->em->clear();

        /** @var Guest $john */
        $john = $this->em->getRepository(Guest::class)->findOneBy(['name' => 'john']);
        $rating = $john->getRating();
        $john->setName('John');
        $this->em->flush();
        $this->assertGraphExist('(g:Guest {name:"John"})-[:RATED {score: 3.5}]->(h:Hotel {name:"Crowne"})');
    }

    public function testEndSideOfRelationshipEntityCanBeUpdated()
    {
        $guest = new Guest('john');
        $hotel = new Hotel('Crowne');
        $rating = new Rating($guest, $hotel, 3.5);
        $guest->setRating($rating);
        $this->em->persist($guest);
        $this->em->flush();
        $this->em->clear();

        /** @var Guest $john */
        $john = $this->em->getRepository(Guest::class)->findOneBy(['name' => 'john']);
        $john->getRating()->getHotel()->setName('Crowne Plaza');
        $this->em->flush();
        $this->assertGraphExist('(g:Guest {name:"john"})-[:RATED {score: 3.5}]->(h:Hotel {name:"Crowne Plaza"})');
    }

    public function testAllSidesCanBeUpdatedAtOnce()
    {
        $guest = new Guest('john');
        $hotel = new Hotel('Crowne');
        $rating = new Rating($guest, $hotel, 3.5);
        $guest->setRating($rating);
        $this->em->persist($guest);
        $this->em->flush();
        $this->em->clear();

        /** @var Guest $john */
        $john = $this->em->getRepository(Guest::class)->findOneBy(['name' => 'john']);
        $john->getRating()->setScore(1.2);
        $john->setName('John');
        $john->getRating()->getHotel()->setName('Crowne Plaza');
        $this->em->flush();
        $this->assertGraphExist('(g:Guest {name:"John"})-[:RATED {score: 1.2}]->(h:Hotel {name:"Crowne Plaza"})');
    }
}
