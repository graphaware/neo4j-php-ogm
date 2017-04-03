<?php

namespace GraphAware\Neo4j\OGM\Util\Converters;

class PropertyConverterFactory
{

    private static $converters = [
        "datetime" => DateTimeTimestampConverter::class
    ];

    public static function getConverter($type)
    {
        if (!array_key_exists($type, self::$converters))
            return null;
        $converter = self::$converters[$type];
        return new $converter;
    }
}