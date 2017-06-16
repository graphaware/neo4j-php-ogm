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

use Doctrine\Common\Persistence\Mapping\ClassMetadata;

abstract class GraphEntityMetadata implements ClassMetadata
{
    /**
     * @var \GraphAware\Neo4j\OGM\Metadata\EntityIdMetadata
     */
    protected $entityIdMetadata;

    /**
     * @var string
     */
    protected $className;

    /**
     * @var \ReflectionClass
     */
    protected $reflectionClass;

    /**
     * @var EntityPropertyMetadata[]
     */
    protected $entityPropertiesMetadata = [];

    /**
     * GraphEntityMetadata constructor.
     *
     * @param \GraphAware\Neo4j\OGM\Metadata\EntityIdMetadata $entityIdMetadata
     * @param string                                          $className
     * @param \ReflectionClass                                $reflectionClass
     * @param $entityPropertiesMetadata
     */
    public function __construct(EntityIdMetadata $entityIdMetadata, $className, \ReflectionClass $reflectionClass, array $entityPropertiesMetadata)
    {
        $this->entityIdMetadata = $entityIdMetadata;
        $this->className = $className;
        $this->reflectionClass = $reflectionClass;
        foreach ($entityPropertiesMetadata as $meta) {
            if ($meta instanceof EntityPropertyMetadata) {
                $this->entityPropertiesMetadata[$meta->getPropertyName()] = $meta;
            }
        }
    }

    public function getName()
    {
        return $this->className;
    }

    public function getReflectionClass()
    {
        return $this->reflectionClass;
    }

    public function isIdentifier($fieldName)
    {
        return $this->entityIdMetadata->getPropertyName() === $fieldName;
    }

    public function hasField($fieldName)
    {
        foreach ($this->entityPropertiesMetadata as $entityPropertyMetadata) {
            if ($entityPropertyMetadata->getPropertyName() === $fieldName) {
                return true;
            }
        }

        return false;
    }

    public function getFieldNames()
    {
        $fields = [];
        $fields[] = $this->entityIdMetadata->getPropertyName();
        foreach ($this->entityPropertiesMetadata as $entityPropertyMetadata) {
            $fields[] = $entityPropertyMetadata->getPropertyName();
        }

        return $fields;
    }

    public function getIdentifierFieldNames()
    {
        return [$this->entityIdMetadata->getPropertyName()];
    }

    public function getTypeOfField($fieldName)
    {
        // TODO: Implement getTypeOfField() method.
        return null;
    }

    public function getIdentifierValues($object)
    {
        return [$this->getIdValue($object)];
    }

    /**
     * @return string
     */
    public function getClassName()
    {
        return $this->className;
    }

    /**
     * @return object
     */
    public function newInstance()
    {
        return $this->reflectionClass->newInstanceWithoutConstructor();
    }

    /**
     * @param $object
     *
     * @return mixed
     */
    public function getIdValue($object)
    {
        return $this->entityIdMetadata->getValue($object);
    }

    /**
     * @param $object
     * @param $value
     */
    public function setId($object, $value)
    {
        $this->entityIdMetadata->setValue($object, $value);
    }

    /**
     * @return string
     */
    public function getIdentifier()
    {
        return $this->entityIdMetadata->getPropertyName();
    }

    /**
     * @return \GraphAware\Neo4j\OGM\Metadata\EntityPropertyMetadata[]
     */
    public function getPropertiesMetadata()
    {
        return $this->entityPropertiesMetadata;
    }

    /**
     * @param $key
     *
     * @return \GraphAware\Neo4j\OGM\Metadata\EntityPropertyMetadata|null
     */
    public function getPropertyMetadata($key)
    {
        if (array_key_exists($key, $this->entityPropertiesMetadata)) {
            return $this->entityPropertiesMetadata[$key];
        }

        return null;
    }

    /**
     * @param object $object
     *
     * @return array
     */
    public function getPropertyValuesArray($object)
    {
        $values = [];
        foreach ($this->entityPropertiesMetadata as $entityPropertyMetadata) {
            $v = $entityPropertyMetadata->getValue($object);
            if (is_object($v)) {
                switch (get_class($v)) {
                    case \DateTime::class:
                        $v = $v->getTimestamp();
                        break;
                }
            }
            $values[$entityPropertyMetadata->getPropertyName()] = $v;
        }

        return $values;
    }

    /**
     * @return string
     */
    public function getEntityAlias()
    {
        return strtolower(str_replace('\\', '_', $this->className));
    }
}
