<?php

/*
 * This file is part of the GraphAware Neo4j PHP OGM package.
 *
 * (c) GraphAware Ltd <info@graphaware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace GraphAware\Neo4j\OGM\Metadata;

use GraphAware\Neo4j\OGM\Annotations\Convert;

class EntityPropertyMetadata
{
    /**
     * @var string
     */
    private $propertyName;

    /**
     * @var \ReflectionProperty
     */
    private $reflectionProperty;

    /**
     * @var \GraphAware\Neo4j\OGM\Metadata\PropertyAnnotationMetadata
     */
    private $propertyAnnotationMetadata;

    /**
     * @var bool
     */
    private $isAccessible;

    private $converter;

    /**
     *
     * @param string                     $propertyName
     * @param \ReflectionProperty        $reflectionProperty
     * @param PropertyAnnotationMetadata $propertyAnnotationMetadata
     * @param Convert                    $converter
     */
    public function __construct($propertyName, \ReflectionProperty $reflectionProperty, PropertyAnnotationMetadata $propertyAnnotationMetadata, Convert $converter = null)
    {
        $this->propertyName = $propertyName;
        $this->reflectionProperty = $reflectionProperty;
        $this->propertyAnnotationMetadata = $propertyAnnotationMetadata;
        $this->isAccessible = $reflectionProperty->isPublic();
        $this->converter = $converter;
    }

    /**
     * @return string
     */
    public function getPropertyName()
    {
        return $this->propertyName;
    }

    /**
     * @return \ReflectionProperty
     */
    public function getReflectionProperty()
    {
        return $this->reflectionProperty;
    }

    /**
     * @param object $object
     * @param mixed  $value
     */
    public function setValue($object, $value)
    {
        $this->checkAccess();
        $this->reflectionProperty->setValue($object, $value);
    }

    /**
     * @return PropertyAnnotationMetadata
     */
    public function getPropertyAnnotationMetadata()
    {
        return $this->propertyAnnotationMetadata;
    }

    /**
     * @param object $object
     *
     * @return mixed
     */
    public function getValue($object)
    {
        $this->checkAccess();

        return $this->reflectionProperty->getValue($object);
    }

    /**
     * @return bool
     */
    public function hasConverter()
    {
        return null !== $this->converter;
    }

    /**
     * @return string|null
     */
    public function getConverterType()
    {
        return $this->converter->type;
    }

    /**
     * @return array
     */
    public function getConverterOptions()
    {
        return $this->converter->options;
    }

    private function checkAccess()
    {
        if (!$this->isAccessible) {
            $this->reflectionProperty->setAccessible(true);
        }
        $this->isAccessible = true;
    }
}
