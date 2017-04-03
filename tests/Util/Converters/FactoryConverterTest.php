<?php
/**
 * Created by PhpStorm.
 * User: rcollas
 * Date: 4/3/2017
 * Time: 11:22 AM
 */

namespace GraphAware\Neo4j\OGM\Tests\Util\Converters;


use GraphAware\Neo4j\OGM\Util\Converters\DateTimeTimestampConverter;
use GraphAware\Neo4j\OGM\Util\Converters\PropertyConverterFactory;
use PHPUnit\Framework\TestCase;

final class FactoryConverterTest extends TestCase
{
    public function testCreateDateTimeTimstampConverter(){
        $converter = PropertyConverterFactory::getConverter("datetime");
        $this->assertInstanceOf(DateTimeTimestampConverter::class,$converter);
    }

    public function testCreateUnexistingConverter(){
        $converter = PropertyConverterFactory::getConverter("unknown");
        $this->assertTrue($converter == null);
    }
}