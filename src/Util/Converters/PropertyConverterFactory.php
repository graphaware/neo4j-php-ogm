<?php

/**
 * Created by PhpStorm.
 * User: Sobraz
 * Date: 01/04/2017
 * Time: 15:45
 */
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