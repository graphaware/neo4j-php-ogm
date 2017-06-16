<?php

/*
 * This file is part of the GraphAware Neo4j PHP OGM package.
 *
 * (c) GraphAware Ltd <info@graphaware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace GraphAware\Neo4j\OGM\Tests\Integration;

use Doctrine\Common\Collections\Criteria;
use Doctrine\Common\Collections\Expr\Comparison;
use GraphAware\Neo4j\OGM\Tests\Integration\Models\Base\User;

/**
 * @group selectable
 */
class SelectableSupportTest extends IntegrationTestCase
{
    public function testForBasicSelectableSupport()
    {
        $this->clearDb();
        $user = new User('M');
        $this->persist($user);
        $this->em->flush();
        $this->em->clear();

        $criteria = new Criteria();
        $criteria->where(new Comparison('login', Comparison::EQ, 'M'));
        $this->assertCount(1, $this->em->getRepository(User::class)->matching($criteria));
    }

    public function testCriteriaWithLimit()
    {
        $this->clearDb();
        $this->client->run('UNWIND range(0, 100) AS i CREATE (n:User {login: i})');

        $criteria = new Criteria();
        $criteria->setMaxResults(10);
        $this->assertCount(10, $this->em->getRepository(User::class)->matching($criteria));
    }
}
