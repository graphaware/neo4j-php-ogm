<?php
/**
 * Created by PhpStorm.
 * User: rcollas
 * Date: 4/3/2017
 * Time: 11:27 AM
 */

namespace GraphAware\Neo4j\OGM\Tests\Util\Converters;

use \GraphAware\Neo4j\OGM\Util\Converters\DateTimeTimestampConverter;

use InvalidArgumentException;
use phpDocumentor\Reflection\Types\Integer;
use PHPUnit\Framework\TestCase;

final class DateTimeTimestampConverterTest extends TestCase
{
    public function testToTimestampConvertion(){
        $converter = new DateTimeTimestampConverter();
        $datetime = new \DateTime("NOW");
        $converted = $converter->getDbValue($datetime);

        $this->assertTrue($converted == $datetime->getTimestamp());
    }

    public function testToTimestampConvertionWithInvalidParam(){
        $this->expectException(InvalidArgumentException::class);

        $converter = new DateTimeTimestampConverter();
        $invalidObject = new Integer();
        $converter->getDbValue($invalidObject);
    }

    public function testToDateTimeConvertion(){
        $converter = new DateTimeTimestampConverter();
        $datetime = new \DateTime("NOW");
        $converted = $converter->getEntytiValue($datetime->getTimestamp());

        $this->assertTrue($converted->getTimestamp() == $datetime->getTimestamp());
    }


}