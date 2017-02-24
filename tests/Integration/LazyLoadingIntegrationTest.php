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

use GraphAware\Neo4j\OGM\Lazy\LazyRelationshipCollection;
use GraphAware\Neo4j\OGM\Tests\Integration\IntegrationTestCase;
use GraphAware\Neo4j\OGM\Tests\Integration\Model\Company;
use GraphAware\Neo4j\OGM\Tests\Integration\Model\User;

/**
 * Class LazyLoadingIntegrationTest.
 *
 * @group lazy-it
 */
class LazyLoadingIntegrationTest extends IntegrationTestCase
{
    public function setUp()
    {
        parent::setUp();
        $this->clearDb();
        $this->init();
    }

    public function testEmployeesAreLazyLoaded()
    {
        /** @var Company $company */
        $company = $this->em->getRepository(Company::class)->findOneBy('name', 'Acme');
        $this->inspectValue($company);
    }

    public function testLazyLoadingRelationships()
    {
        /** @var Company $company */
        $company = $this->em->getRepository(Company::class)->findOneBy('name', 'Acme');
        $this->assertCount(10, $company->getEmployees());
        $employee = $company->getEmployees()[0];
        $this->assertInstanceOf(LazyRelationshipCollection::class, $employee->getFriends());
    }

    public function testCanTraverseRecursively()
    {
        /** @var Company $company */
        $company = $this->em->getRepository(Company::class)->findOneBy('name', 'Acme');
        $this->assertCount(10, $company->getEmployees());
        $employee = $company->getEmployees()[0];
        $this->assertInstanceOf(LazyRelationshipCollection::class, $employee->getLovedBy());
        $this->assertCount(9, $employee->getLovedBy());
        $this->assertCount(9, $employee->getLoves());
    }

    private function inspectValue($object)
    {
        $reflClass = new \ReflectionClass(Company::class);
        $property = $reflClass->getProperty('employees');
        $property->setAccessible(true);
        $v = $property->getValue($object);
        $this->assertInstanceOf(LazyRelationshipCollection::class, $v);
    }

    private function init()
    {
        $company = new Company('Acme');
        for ($i = 0; $i < 10; ++$i) {
            $u = new User('DummyUser'.$i);
            $u->setCurrentCompany($company);
            $company->addEmployee($u);
        }
        foreach ($company->getEmployees() as $employee) {
            foreach ($company->getEmployees() as $employee2) {
                if ($employee->getLogin() !== $employee2->getLogin()) {
                    $employee2->addLoves($employee);
                    $employee->addLovedBy($employee2);
                }
            }
        }
        $this->em->persist($company);
        $this->em->flush();
    }
}
