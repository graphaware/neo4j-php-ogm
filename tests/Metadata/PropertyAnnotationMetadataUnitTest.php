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

use GraphAware\Neo4j\OGM\Metadata\PropertyAnnotationMetadata;

/**
 * Class PropertyMetadataUnitTest.
 *
 * @group metadata
 */
class PropertyAnnotationMetadataUnitTest extends \PHPUnit_Framework_TestCase
{
    public function testInit()
    {
        $metadata = new PropertyAnnotationMetadata('string');
        $this->assertSame('string', $metadata->getType());
    }

    public function testIsNullableByDefault()
    {
        $metadata = new PropertyAnnotationMetadata('string');
        $this->assertTrue($metadata->isNullable());
    }

    public function testNotHaveCustomKeyByDefault()
    {
        $metadata = new PropertyAnnotationMetadata('string');
        $this->assertFalse($metadata->hasCustomKey());
    }

    public function testNotNullableCanBeDefined()
    {
        $metadata = new PropertyAnnotationMetadata('string', null, false);
        $this->assertFalse($metadata->isNullable());
    }

    public function testCustomKeyCanBePassed()
    {
        $metadata = new PropertyAnnotationMetadata('string', 'dob');
        $this->assertSame('dob', $metadata->getKey());
    }
}
