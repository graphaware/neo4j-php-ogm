<?php

namespace GraphAware\Neo4j\OGM\Util\Converters;

class DateTimeTimestampConverter extends PropertyConverter
{

    protected function convertToDbValue($entityValue)
    {
        /** @var $entityValue \DateTime */
        return $entityValue->getTimestamp();
    }

    protected function convertToPropertyEnttityValue($dbvalue)
    {
        $datetime = new \DateTime();
        $datetime->setTimestamp($dbvalue);
        return $datetime;
    }

    protected static function getPropertyEntityType() : string
    {
        return "\\DateTime";
    }
}