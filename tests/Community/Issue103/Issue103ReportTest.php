<?php

namespace GraphAware\Neo4j\OGM\Tests\Community\Issue103;

use GraphAware\Neo4j\OGM\Tests\Integration\IntegrationTestCase;

/**
 *
 * @group issue-103
 */
class Issue103ReportTest extends IntegrationTestCase
{
    public function setUp()
    {
        parent::setUp();
        $this->clearDb();
    }

    public function testIssueReport()
    {
        $manager = $this->em;

        // CREATE ENTITY
        $test_entityUuid = 1234;
        $entity = new Entity($test_entityUuid);

        // CREATE CONTEXT
        $test_contextUuid = 456;
        $context = new Context($test_contextUuid);
        $context->setEntity($entity);

        // ADD CONTEXT TO ENTITY
        $entity->addContext($context);


        // SAVE EVERYTHING
        $manager->persist($entity);
        $manager->persist($context);
        $manager->flush();

        // LOOK UP CONTEXT
        $context2 = $manager->getRepository(Context::class)->findOneBy(array('name' => $test_contextUuid));
        $this->assertEquals(spl_object_hash($context), spl_object_hash($context2));
        $this->assertEquals($test_entityUuid, $context2->getEntity()->getName());
    }
}