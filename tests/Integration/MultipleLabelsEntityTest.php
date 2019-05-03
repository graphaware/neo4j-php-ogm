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


use GraphAware\Neo4j\OGM\Tests\Integration\Models\MultipleLabelsEntity\Person;
use GraphAware\Neo4j\OGM\Tests\Integration\Models\MultipleLabelsEntity\Student;
use GraphAware\Neo4j\OGM\Tests\Integration\Models\MultipleLabelsEntity\Worker;

class MultipleLabelsEntityTest extends IntegrationTestCase
{
    public function setUp()
    {
        parent::setUp();
        $this->clearDb();
    }

    public function testUsage()
    {
        $student = new Student('First student', 'Last student', 4);
        $this->em->persist($student);

        $worker = new Worker('First worker', 'Last worker', 'ITSM');
        $this->em->persist($worker);

        $this->em->flush();

        $persons = $this->em->getRepository(Person::class)->findAll();
        self::assertCount(2, $persons);

        foreach ($persons as $person) {
            self::assertInstanceOf(Person::class, $person);
        }

        $student = $this->em->getRepository(Student::class)->findOneBy(['first' => 'First student']);
        self::assertInstanceOf(Student::class, $student);

        $worker = $this->em->getRepository(Worker::class)->findOneBy(['company' => 'ITSM']);
        self::assertInstanceOf(Worker::class, $worker);

        $this->em->remove($student);
        $this->em->remove($worker);
        $this->em->flush();

        $persons = $this->em->getRepository(Person::class)->findAll();
        self::assertCount(0, $persons);
    }
}
