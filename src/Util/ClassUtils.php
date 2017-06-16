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

class ClassUtils
{
    /**
     * @param string $class
     * @param string $pointOfView
     *
     * @return string
     */
    public static function getFullClassName($class, $pointOfView)
    {
        $expl = explode('\\', $class);
        if (1 === count($expl)) {
            $expl2 = explode('\\', $pointOfView);
            if (1 !== count($expl2)) {
                unset($expl2[count($expl2) - 1]);
                $class = sprintf('%s\\%s', implode('\\', $expl2), $class);
            }
        }

        if (!class_exists($class)) {
            throw new \RuntimeException(sprintf('The class "%s" could not be found', $class));
        }

        return $class;
    }
}
