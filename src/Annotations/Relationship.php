<?php

/*
 * This file is part of the GraphAware Neo4j PHP OGM package.
 *
 * (c) GraphAware Ltd <info@graphaware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace GraphAware\Neo4j\OGM\Annotations;

/**
 * @Annotation
 * @Target({"PROPERTY"})
 */
final class Relationship
{
    /**
     * @var string
     */
    public $targetEntity;

    /**
     * @var string
     */
    public $relationshipEntity;

    /**
     * @var string
     */
    public $type;

    /**
     * @var string
     *
     * @Enum({"OUTGOING","INCOMING","BOTH"})
     */
    public $direction;

    /**
     * @var string
     */
    public $mappedBy;

    /**
     * @var bool
     */
    public $collection;
}
