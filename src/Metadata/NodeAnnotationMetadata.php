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

final class NodeAnnotationMetadata
{
    /**
     * @var string[]
     */
    private $labels;

    /**
     * @var string
     */
    private $customRepository;

    /**
     * @param string[]    $labels
     * @param string|null $repository
     */
    public function __construct($labels, $repository)
    {
        $this->labels = $labels;
        $this->customRepository = $repository;
    }

    /**
     * @return string[]
     */
    public function getLabels()
    {
        return $this->labels;
    }

    /**
     * @return string
     */
    public function getCustomRepository()
    {
        return $this->customRepository;
    }
}
