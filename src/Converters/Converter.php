<?php

/*
 * This file is part of the GraphAware Neo4j PHP OGM package.
 *
 * (c) GraphAware Ltd <info@graphaware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace GraphAware\Neo4j\OGM\Converters;

abstract class Converter
{
    const DATETIME = 'datetime';

    protected $propertyName;

    private static $converterMap = [
        self::DATETIME => DateTimeConverter::class,
    ];

    private static $converterObjects = [];

    /**
     * @param string $propertyName
     */
    final private function __construct($propertyName)
    {
        $this->propertyName = $propertyName;
    }

    abstract public function getName();

    abstract public function toDatabaseValue($value, array $options);

    abstract public function toPHPValue(array $values, array $options);

    /**
     * @param string $name
     *
     * @return Converter
     */
    public static function getConverter($name, $propertyName)
    {
        $objectK = $name.$propertyName;
        if ( ! isset(self::$converterObjects[$objectK])) {
            if ( ! isset(self::$converterMap[$name])) {
                throw new \InvalidArgumentException(sprintf('No converter named "%s" found', $name));
            }

            self::$converterObjects[$objectK] = new self::$converterMap[$name]($propertyName);
        }

        return self::$converterObjects[$objectK];
    }

    /**
     * @param string $name
     * @param string $class
     */
    public static function addConverter($name, $class)
    {
        if ( isset(self::$converterMap[$name])) {
            throw new \InvalidArgumentException(sprintf('Converter with name "%s" already exist', $name));
        }

        self::$converterMap[$name] = $class;
    }

    /**
     * @param string $name
     *
     * @return bool
     */
    public static function hasConverter($name)
    {
        return isset(self::$converterMap[$name]);
    }


}