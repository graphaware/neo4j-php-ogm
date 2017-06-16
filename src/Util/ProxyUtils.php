<?php

/*
 * This file is part of the GraphAware Neo4j PHP OGM package.
 *
 * (c) GraphAware Ltd <info@graphaware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace GraphAware\Neo4j\OGM\Util;

class ProxyUtils
{
    public static function getPropertyIdentifier(\ReflectionProperty $reflectionProperty, $className)
    {
        $key = null;
        if ($reflectionProperty->isPrivate()) {
            $key = '\\0'.$className.'\\0'.$reflectionProperty->getName();
        } elseif ($reflectionProperty->isProtected()) {
            $key = ''."\0".'*'."\0".$reflectionProperty->getName();
        } elseif ($reflectionProperty->isPublic()) {
            $key = $reflectionProperty->getName();
        }

        if (null === $key) {
            throw new \InvalidArgumentException('Unable to detect property visibility');
        }

        return $key;
    }
}
