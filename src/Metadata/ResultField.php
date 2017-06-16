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

class ResultField
{
    const FIELD_TYPE_ENTITY = 'ENTITY';

    protected $fieldName;

    protected $fieldType;

    protected $target;

    /**
     * @var null|\GraphAware\Neo4j\OGM\Metadata\ClassMetadata
     */
    protected $targetMetadata;

    public function __construct($fieldName, $fieldType, $target)
    {
        $this->fieldName = $fieldName;
        $this->fieldType = $fieldType;
        $this->target = $target;
    }

    /**
     * @return mixed
     */
    public function getFieldName()
    {
        return $this->fieldName;
    }

    /**
     * @return mixed
     */
    public function getFieldType()
    {
        return $this->fieldType;
    }

    /**
     * @return mixed
     */
    public function getTarget()
    {
        return $this->target;
    }

    public function isEntity()
    {
        return $this->fieldType === self::FIELD_TYPE_ENTITY;
    }

    /**
     * @param \GraphAware\Neo4j\OGM\Metadata\GraphEntityMetadata $metadata
     */
    public function setMetadata(GraphEntityMetadata $metadata)
    {
        $this->targetMetadata = $metadata;
    }

    /**
     * @return \GraphAware\Neo4j\OGM\Metadata\ClassMetadata|null
     */
    public function getTargetMetadata()
    {
        return $this->targetMetadata;
    }
}
