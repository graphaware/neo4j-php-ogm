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

final class EntityIdMetadata
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
     * @var \GraphAware\Neo4j\OGM\Metadata\IdAnnotationMetadata
     */
    private $idAnnotationMetadata;

    /**
     * @param string                                              $propertyName
     * @param \ReflectionProperty                                 $reflectionProperty
     * @param \GraphAware\Neo4j\OGM\Metadata\IdAnnotationMetadata $idAnnotationMetadata
     */
    public function __construct($propertyName, \ReflectionProperty $reflectionProperty, IdAnnotationMetadata $idAnnotationMetadata)
    {
        $this->propertyName = $propertyName;
        $this->reflectionProperty = $reflectionProperty;
        $this->idAnnotationMetadata = $idAnnotationMetadata;
    }

    /**
     * @param $object
     *
     * @return mixed
     */
    public function getValue($object)
    {
        $this->reflectionProperty->setAccessible(true);

        return $this->reflectionProperty->getValue($object);
    }

    /**
     * @param $object
     * @param $value
     */
    public function setValue($object, $value)
    {
        $this->reflectionProperty->setAccessible(true);
        $this->reflectionProperty->setValue($object, $value);
    }

    /**
     * @return string
     */
    public function getPropertyName()
    {
        return $this->propertyName;
    }
}
