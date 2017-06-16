<?php

namespace GraphAware\Neo4j\OGM\Tests\Integration;

use GraphAware\Neo4j\OGM\Tests\Integration\Models\MoviesDemo\Movie;
use GraphAware\Neo4j\OGM\Tests\Integration\Repository\MoviesCustomRepository;

/**
 *
 * @group custom-repository
 */
class CustomRepositoryTest extends IntegrationTestCase
{
   public function setUp()
   {
       parent::setUp();
       $this->clearDb();
       $this->playMovies();
   }

   public function testCustomRepositoryIsUsed()
   {
       $repository = $this->em->getRepository(Movie::class);
       $this->assertInstanceOf(MoviesCustomRepository::class, $repository);
   }

   public function testCustomRepositoryInheritsBaseRepositoryMethods()
   {
       /** @var MoviesCustomRepository $repository */
       $repository = $this->em->getRepository(Movie::class);
       $movies = $repository->findAll();
       $this->assertCount(38, $movies);
   }

   public function testMethodsOnCustomRepositoryAreUsed()
   {
       /** @var MoviesCustomRepository $repository */
       $repository = $this->em->getRepository(Movie::class);

       $result = $repository->findAllWithScore();
       $this->assertInstanceOf(Movie::class, $result[0]['n']);
       $this->assertEquals(12, $result[0]['score']);
   }
}