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

use Doctrine\Common\Annotations\Annotation\Target;

/**
 * @Annotation
 * @Target({"PROPERTY"})
 */
final class MappedResult
{
    /**
     * @var string
     *
     * @Enum({"ENTITY","STRING","BOOLEAN","ARRAY","FLOAT","INTEGER"})
     */
    public $type;

    /**
     * @var string
     */
    public $target;
}
