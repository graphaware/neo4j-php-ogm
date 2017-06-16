<?php

/*
 * This file is part of the GraphAware Neo4j PHP OGM package.
 *
 * (c) GraphAware Ltd <info@graphaware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace GraphAware\Neo4j\OGM;

final class Events
{
    const PRE_FLUSH = 'preFlush';
    const ON_FLUSH = 'onFlush';
    const POST_FLUSH = 'postFlush';

    /**
     * Prevent class from being instantiated.
     */
    private function __construct()
    {
    }
}
