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
     * @var string
     */
    private $label;

    /**
     * @var string
     */
    private $customRepository;

    /**
     * @param string      $label
     * @param string|null $repository
     */
    public function __construct($label, $repository)
    {
        $this->label = $label;
        $this->customRepository = $repository;
    }

    /**
     * @return string
     */
    public function getLabel()
    {
        return $this->label;
    }

    /**
     * @return string
     */
    public function getCustomRepository()
    {
        return $this->customRepository;
    }
}
