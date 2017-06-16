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

final class GraphEntityPropertyMetadata
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
     * GraphEntityPropertyMetadata constructor.
     *
     * @param string                                                    $propertyName
     * @param \ReflectionProperty                                       $reflectionProperty
     * @param \GraphAware\Neo4j\OGM\Metadata\PropertyAnnotationMetadata $propertyAnnotationMetadata
     */
    public function __construct($propertyName, \ReflectionProperty $reflectionProperty, PropertyAnnotationMetadata $propertyAnnotationMetadata)
    {
        $this->propertyName = $propertyName;
        $this->reflectionProperty = $reflectionProperty;
        $this->propertyAnnotationMetadata = $propertyAnnotationMetadata;
    }

    /**
     * @return string
     */
    public function getPropertyName()
    {
        return $this->propertyName;
    }

    /**
     * @return \GraphAware\Neo4j\OGM\Metadata\PropertyAnnotationMetadata
     */
    public function getPropertyAnnotationMetadata()
    {
        return $this->propertyAnnotationMetadata;
    }
}
