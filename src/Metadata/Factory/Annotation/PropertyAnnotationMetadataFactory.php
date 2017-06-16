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
use GraphAware\Neo4j\OGM\Annotations\Property;
use GraphAware\Neo4j\OGM\Metadata\PropertyAnnotationMetadata;

final class PropertyAnnotationMetadataFactory
{
    private $reader;

    public function __construct(Reader $reader)
    {
        $this->reader = $reader;
    }

    public function create($entityClass, $property)
    {
        $reflectionClass = new \ReflectionClass($entityClass);
        if ($reflectionClass->hasProperty($property)) {
            /** @var Property $annotation */
            $annotation = $this->reader->getPropertyAnnotation($reflectionClass->getProperty($property), Property::class);

            if (null !== $annotation) {
                return new PropertyAnnotationMetadata($annotation->type, $annotation->key, $annotation->nullable);
            }
        }
    }
}
