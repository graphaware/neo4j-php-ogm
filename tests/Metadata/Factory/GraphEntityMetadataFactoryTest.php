<?php

/*
 * This file is part of the GraphAware Neo4j PHP OGM package.
 *
 * (c) GraphAware Ltd <info@graphaware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace GraphAware\Neo4j\OGM\Tests\Metadata\Factory;

use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\Common\Persistence\Mapping\Driver\SymfonyFileLocator;
use GraphAware\Neo4j\OGM\Metadata\EntityPropertyMetadata;
use GraphAware\Neo4j\OGM\Metadata\Factory\Annotation\AnnotationGraphEntityMetadataFactory;
use GraphAware\Neo4j\OGM\Metadata\Factory\Xml\IdXmlMetadataFactory;
use GraphAware\Neo4j\OGM\Metadata\Factory\Xml\NodeEntityMetadataFactory;
use GraphAware\Neo4j\OGM\Metadata\Factory\Xml\PropertyXmlMetadataFactory;
use GraphAware\Neo4j\OGM\Metadata\Factory\Xml\RelationshipEntityMetadataFactory;
use GraphAware\Neo4j\OGM\Metadata\Factory\Xml\RelationshipXmlMetadataFactory;
use GraphAware\Neo4j\OGM\Metadata\Factory\Xml\XmlGraphEntityMetadataFactory;
use GraphAware\Neo4j\OGM\Metadata\NodeEntityMetadata;
use GraphAware\Neo4j\OGM\Metadata\RelationshipEntityMetadata;
use GraphAware\Neo4j\OGM\Tests\Metadata\Factory\Fixtures\Movie;
use GraphAware\Neo4j\OGM\Tests\Metadata\Factory\Fixtures\MovieRepository;
use GraphAware\Neo4j\OGM\Tests\Metadata\Factory\Fixtures\Person;
use GraphAware\Neo4j\OGM\Tests\Metadata\Factory\Fixtures\Rating;

/**
 * Class GraphEntityMetadataFactoryTest.
 *
 * @group xml-mapping
 */
class GraphEntityMetadataFactoryTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var AnnotationGraphEntityMetadataFactory
     */
    private $annotationMetadataFactory;

    /**
     * @var XmlGraphEntityMetadataFactory
     */
    private $xmlMetadataFactory;

    protected function setUp()
    {
        $this->annotationMetadataFactory = new AnnotationGraphEntityMetadataFactory(new AnnotationReader());
        $this->xmlMetadataFactory = new XmlGraphEntityMetadataFactory(
            new SymfonyFileLocator(
                [__DIR__.'/Fixtures/graphaware' => 'GraphAware\\Neo4j\\OGM\\Tests\\Metadata\\Factory\\Fixtures'],
                '.ogm.xml'
            ),
            new NodeEntityMetadataFactory(
                new PropertyXmlMetadataFactory(),
                new RelationshipXmlMetadataFactory(),
                new IdXmlMetadataFactory()
            ),
            new RelationshipEntityMetadataFactory(new PropertyXmlMetadataFactory(), new IdXmlMetadataFactory())
        );
    }

    public function testMovieMetadata()
    {
        $this->assertMovieMetadata($this->annotationMetadataFactory->create(Movie::class));
        $this->assertMovieMetadata($this->xmlMetadataFactory->create(Movie::class));
    }

    public function testPersonMetadata()
    {
        $this->assertPersonMetadata($this->annotationMetadataFactory->create(Person::class));
        $this->assertPersonMetadata($this->xmlMetadataFactory->create(Person::class));
    }

    /**
     * @group property-convert
     */
    public function testPropertyConvertMetadata()
    {
        $personMetadata = $this->annotationMetadataFactory->create(Person::class);
        /** @var EntityPropertyMetadata $createdMetadata */
        $createdMetadata = $personMetadata->getPropertyMetadata('created');
        $this->assertTrue($createdMetadata->hasConverter());
        $this->assertEquals('datetime', $createdMetadata->getConverterType());
        $this->assertInternalType('array', $createdMetadata->getConverterOptions());
        $this->assertArrayHasKey('db_format', $createdMetadata->getConverterOptions());
    }

    public function testRatingMetadata()
    {
        $this->assertRatingMetadata($this->annotationMetadataFactory->create(Rating::class));
        $this->assertRatingMetadata($this->xmlMetadataFactory->create(Rating::class));
    }

    public function testMappingValidAccordingToSchema()
    {
        $filesIterator = new \GlobIterator(__DIR__.'/Fixtures/graphaware/*.ogm.xml');

        $previous = libxml_use_internal_errors(true);
        foreach ($filesIterator as $fileInfo) {
            $dom = new \DOMDocument();
            $dom->loadXML(file_get_contents($fileInfo->getPathName()));

            if (!$dom->schemaValidate(getenv('basedir').'graphaware-mapping.xsd')) {
                $this->fail(sprintf(
                    'Mapping file "%s" is not valid according to schema: %s',
                    $fileInfo->getFileName(),
                    libxml_get_last_error()->message
                ));
            }
        }
        libxml_use_internal_errors($previous);
    }

    /**
     * @param NodeEntityMetadata|RelationshipEntityMetadata $metadata
     */
    private function assertRatingMetadata($metadata)
    {
        $this->assertSame('RATED', $metadata->getType());
        $this->assertSame('id', $metadata->getIdentifier());
        $this->assertSame(Person::class, $metadata->getStartNode());
        $this->assertSame(Movie::class, $metadata->getEndNode());

        $properties = $metadata->getPropertiesMetadata();
        $this->assertCount(1, $properties);

        $scoreProperty = $metadata->getPropertyMetadata('score');
        $this->assertSame('float', $scoreProperty->getPropertyAnnotationMetadata()->getType());
        $this->assertSame(true, $scoreProperty->getPropertyAnnotationMetadata()->isNullable());
    }

    /**
     * @param NodeEntityMetadata|RelationshipEntityMetadata $metadata
     */
    private function assertPersonMetadata($metadata)
    {
        $this->assertSame('Person', $metadata->getLabel());
        $this->assertSame(false, $metadata->hasCustomRepository());

        $this->assertSame('id', $metadata->getIdentifier());

        $properties = $metadata->getPropertiesMetadata();

        $nameProperty = $metadata->getPropertyMetadata('name');
        $this->assertSame('string', $nameProperty->getPropertyAnnotationMetadata()->getType());
        $this->assertSame(true, $nameProperty->getPropertyAnnotationMetadata()->isNullable());

        $ageProperty = $metadata->getPropertyMetadata('age');
        $this->assertSame('int', $ageProperty->getPropertyAnnotationMetadata()->getType());
        $this->assertSame(false, $ageProperty->getPropertyAnnotationMetadata()->isNullable());

        $this->assertCount(1, $metadata->getLabeledProperties());
        $labeledAgeProperty = $metadata->getLabeledProperty('age');
        $this->assertSame('my-age', $labeledAgeProperty->getLabelName());

        $relations = $metadata->getRelationships();
        $this->assertCount(1, $relations);

        $moviesRelation = $metadata->getRelationship('movies');
        $this->assertSame('ACTED_IN', $moviesRelation->getType());
        $this->assertSame('OUTGOING', $moviesRelation->getDirection());
        $this->assertSame(Movie::class, $moviesRelation->getTargetEntity());
        $this->assertSame(true, $moviesRelation->isCollection());
        $this->assertSame('actors', $moviesRelation->getMappedByProperty());
    }

    /**
     * @param NodeEntityMetadata|RelationshipEntityMetadata $metadata
     */
    private function assertMovieMetadata($metadata)
    {
        $this->assertSame('Movie', $metadata->getLabel());
        $this->assertSame(MovieRepository::class, $metadata->getRepositoryClass());

        $this->assertSame('id', $metadata->getIdentifier());

        $properties = $metadata->getPropertiesMetadata();
        $this->assertCount(1, $properties);

        $nameProperty = $metadata->getPropertyMetadata('name');
        $this->assertSame('string', $nameProperty->getPropertyAnnotationMetadata()->getType());
        $this->assertSame(true, $nameProperty->getPropertyAnnotationMetadata()->isNullable());

        $relations = $metadata->getRelationships();
        $this->assertCount(1, $relations);

        $actorsRelation = $metadata->getRelationship('actors');
        $this->assertSame('ACTED_IN', $actorsRelation->getType());
        $this->assertSame('OUTGOING', $actorsRelation->getDirection());
        $this->assertSame(Person::class, $actorsRelation->getTargetEntity());
        $this->assertSame(true, $actorsRelation->isCollection());
        $this->assertSame(false, $actorsRelation->isLazy());
        $this->assertSame(false, $actorsRelation->hasOrderBy());
    }
}
