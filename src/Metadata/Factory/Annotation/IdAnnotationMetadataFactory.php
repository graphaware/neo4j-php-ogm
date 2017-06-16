<?php

/*
 * This file is part of the GraphAware Neo4j PHP OGM package.
 *
 * (c) GraphAware Ltd <info@graphaware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace GraphAware\Neo4j\OGM\Metadata\Factory\Annotation;

use Doctrine\Common\Annotations\Reader;
use GraphAware\Neo4j\OGM\Annotations\GraphId;
use GraphAware\Neo4j\OGM\Metadata\IdAnnotationMetadata;

class IdAnnotationMetadataFactory
{
    /**
     * @var \Doctrine\Common\Annotations\Reader
     */
    private $reader;

    /**
     * @param \Doctrine\Common\Annotations\Reader $reader
     */
    public function __construct(Reader $reader)
    {
        $this->reader = $reader;
    }

    public function create($class, \ReflectionProperty $reflectionProperty)
    {
        $annotations = $this->reader->getPropertyAnnotations($reflectionProperty);
        foreach ($annotations as $annotation) {
            if ($annotation instanceof GraphId) {
                return new IdAnnotationMetadata();
            }
        }
    }
}
