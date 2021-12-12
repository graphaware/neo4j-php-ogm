<?php

/*
 * This file is part of the GraphAware Neo4j PHP OGM package.
 *
 * (c) GraphAware Ltd <info@graphaware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace GraphAware\Neo4j\OGM\Tests\Integration\Models\MultipleLabelsEntity;

use GraphAware\Neo4j\OGM\Annotations as OGM;

/**
 * Class Worker.
 *
 * @OGM\Node(labels={"Worker", "Person"})
 */
class Worker extends Person
{
    /**
     * @var string
     *
     * @OGM\Property(type="string", nullable=false)
     */
    private $company;

    /**
     * Student constructor.
     *
     * @param $first
     * @param $last
     * @param $company
     */
    public function __construct($first, $last, $company)
    {
        parent::__construct($first, $last);

        $this->company = $company;
    }

    /**
     * @return string
     */
    public function getCompany()
    {
        return $this->company;
    }

    /**
     * @param $company
     *
     * @return $this
     */
    public function setCompany($company)
    {
        $this->company = $company;

        return $this;
    }
}
