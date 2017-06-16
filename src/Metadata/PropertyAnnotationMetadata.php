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

final class PropertyAnnotationMetadata
{
    /**
     * @var string
     */
    private $type;

    /**
     * @var null|string
     */
    private $key;

    /**
     * @var bool
     */
    private $nullable;

    public function __construct($type, $key = null, $nullable = null)
    {
        $this->type = $type;
        $this->key = $key;
        $this->nullable = null !== $nullable ? (bool) $nullable : true;
    }

    /**
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @return bool
     */
    public function hasCustomKey()
    {
        return null !== $this->key;
    }

    /**
     * @return null|string
     */
    public function getKey()
    {
        return $this->key;
    }

    /**
     * @return bool
     */
    public function isNullable()
    {
        return $this->nullable;
    }
}
