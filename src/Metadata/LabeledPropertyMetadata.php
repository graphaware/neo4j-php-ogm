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

use GraphAware\Neo4j\OGM\Annotations\Label;

final class LabeledPropertyMetadata
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
     * @var \GraphAware\Neo4j\OGM\Annotations\Label
     */
    private $annotation;

    /**
     * @var string
     */
    private $labelName;

    /**
     * @param string                                  $propertyName
     * @param \ReflectionProperty                     $reflectionProperty
     * @param \GraphAware\Neo4j\OGM\Annotations\Label $annotation
     */
    public function __construct($propertyName, \ReflectionProperty $reflectionProperty, Label $annotation)
    {
        $this->propertyName = $propertyName;
        $this->reflectionProperty = $reflectionProperty;
        $this->annotation = $annotation;
        $this->labelName = $annotation->name;
    }

    /**
     * @return string
     */
    public function getPropertyName()
    {
        return $this->propertyName;
    }

    /**
     * @param object $object
     *
     * @return mixed
     */
    public function getValue($object)
    {
        $this->reflectionProperty->setAccessible(true);

        return $this->reflectionProperty->getValue($object);
    }

    /**
     * @return string
     */
    public function getLabelName()
    {
        return $this->labelName;
    }

    /**
     * @param object $object
     * @param bool   $value
     */
    public function setLabel($object, $value)
    {
        $this->reflectionProperty->setAccessible(true);
        $this->reflectionProperty->setValue($object, $value);
    }

    /**
     * @param object $object
     *
     * @return bool
     */
    public function isLabelSet($object)
    {
        $this->reflectionProperty->setAccessible(true);
        $v = $this->reflectionProperty->getValue($object);
        if (true === $v) {
            return true;
        }

        return false;
    }
}
