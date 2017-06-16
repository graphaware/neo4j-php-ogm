<?php

/*
 * This file is part of the GraphAware Neo4j PHP OGM package.
 *
 * (c) GraphAware Ltd <info@graphaware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace GraphAware\Neo4j\OGM\Tests\Metadata;

use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\Common\Annotations\FileCacheReader;
use GraphAware\Neo4j\OGM\Metadata\EntityPropertyMetadata;
use GraphAware\Neo4j\OGM\Metadata\Factory\Annotation\AnnotationGraphEntityMetadataFactory;
use GraphAware\Neo4j\OGM\Metadata\GraphEntityMetadata;
use GraphAware\Neo4j\OGM\Metadata\NodeEntityMetadata;
use GraphAware\Neo4j\OGM\Tests\Metadata\Factory\Fixtures\Person;

/**
 * Class MetadataFactoryITest.
 *
 * @group metadata-factory-it
 */
class MetadataFactoryITest extends \PHPUnit_Framework_TestCase
{
    protected $annotationReader;

    /**
     * @var AnnotationGraphEntityMetadataFactory
     */
    protected $entityMetadataFactory;

    public function setUp()
    {
        parent::setUp();
        $this->annotationReader = new FileCacheReader(
            new AnnotationReader(),
            getenv('proxydir'),
            true
        );

        $this->entityMetadataFactory = new AnnotationGraphEntityMetadataFactory($this->annotationReader);
    }

    public function testNodeEntityMetadataIsCreated()
    {
        $entityMetadata = $this->entityMetadataFactory->create(Person::class);
        $this->assertInstanceOf(GraphEntityMetadata::class, $entityMetadata);
        $this->assertInstanceOf(NodeEntityMetadata::class, $entityMetadata);
        $this->assertCount(3, $entityMetadata->getPropertiesMetadata());
        $this->assertInstanceOf(EntityPropertyMetadata::class, $entityMetadata->getPropertyMetadata('name'));
    }

    public function testNewInstancesOfGivenClassCanBeCreate()
    {
        $entityMetadata = $this->entityMetadataFactory->create(Person::class);
        $o = $entityMetadata->newInstance();
        $this->assertInstanceOf(Person::class, $o);
    }

    public function testValueCanBeSetOnInstantiatedObject()
    {
        $entityMetadata = $this->entityMetadataFactory->create(Person::class);
        /** @var Person $o */
        $o = $entityMetadata->newInstance();
        $entityMetadata->getPropertyMetadata('name')->setValue($o, 'John');
        $this->assertSame('John', $o->getName());
    }
}
