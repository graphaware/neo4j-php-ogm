<?php

namespace GraphAware\Neo4j\OGM\Util\Converters;

class PropertyConverterFactory
{

    private static $converters = [
        "datetime" => "clazz"
    ];

    public static function getConverter($type) : PropertyConverter
    {
        if (!array_key_exists($type, self::$converters))
            return null;
        $converter = self::$converters[$type];
        return new $converter;
    }
}