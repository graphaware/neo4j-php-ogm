<?php

namespace GraphAware\Neo4j\OGM\Tests;

class UnitOfWorkTest extends \PHPUnit_Framework_TestCase
{
    /*
    public function testTraverseRelationshipEntities_makeSureAllRelationsAreVisited()
    {
        $fooRelationMetadata = $this->getMockBuilder(DummyRelationMetadata::class)
            ->setMethods(['getValue'])
            ->getMock();
        $barRelationMetadata = $this->getMockBuilder(DummyRelationMetadata::class)
            ->setMethods(['getValue'])
            ->getMock();

        $fooRelationMetadata->expects($this->once())
            ->method('getValue')
            ->willReturn(null);
        $barRelationMetadata->expects($this->once())
            ->method('getValue')
            ->willReturn(null);

        $baseClassMetadata = $this->getMockBuilder(DummyRelationMetadata::class)
            ->setMethods(['getRelationshipEntities'])
            ->getMock();

        $baseClassMetadata->expects($this->any())
            ->method('getRelationshipEntities')
            ->willReturn([$fooRelationMetadata, $barRelationMetadata]);

        $entityManager = $this->getMockBuilder('GraphAware\Neo4j\OGM\EntityManager')
            ->disableOriginalConstructor()
            ->setMethods(['getClassMetadataFor', 'persistRelationshipEntity'])
            ->getMock();

        $entityManager->expects($this->once())
            ->method('getClassMetadataFor')
            ->with($this->equalTo(Movie::class))
            ->willReturn($baseClassMetadata);

        $unitOfWork = $this->getMockBuilder('GraphAware\Neo4j\OGM\UnitOfWork')
            ->setConstructorArgs([$entityManager])
            ->setMethods(['persistRelationshipEntity', 'doPersist'])
            ->getMock();

        $movie = new Movie();
        $unitOfWork->traverseRelationshipEntities($movie);
    }
}


class DummyRelationMetadata {
    public function getValue()
    {
        return null;
    }
    public function getRelationshipEntities()
    {
        return null;
    }
     *
     **/
}
