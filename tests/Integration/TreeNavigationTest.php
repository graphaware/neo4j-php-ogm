<?php

namespace GraphAware\Neo4j\OGM\Tests\Integration;
use GraphAware\Neo4j\OGM\Tests\Integration\Models\Tree\Level;

/**
 *
 * @group tree-nav
 */
class TreeNavigationTest extends IntegrationTestCase
{
   public function setUp()
   {
       parent::setUp();
       $this->clearDb();
   }

   public function testTreeNavigationModelPersistence()
   {
       $this->createTree();
       $this->assertGraphExist('(l:Level {code:"root"})<-[:PARENT_LEVEL*3]-(l3:Level {code:"l3a"})');
       $this->assertGraphExist('(l:Level {code:"root"})<-[:PARENT_LEVEL*2]-(l3:Level {code:"l2a"})');
       $this->assertGraphExist('(l:Level {code:"root"})<-[:PARENT_LEVEL*2]-(l3:Level {code:"l2b"})');
       $this->assertGraphExist('(l:Level {code:"l1a"})<-[:PARENT_LEVEL*1]-(l3:Level {code:"l2a"})');
       $this->assertGraphExist('(l:Level {code:"l1b"})<-[:PARENT_LEVEL*1]-(l3:Level {code:"l2c"})');
   }

   public function testRootCanNavigateToBottomLevelOnceFetched()
   {
       $this->createTree();
       /** @var Level $root */
       $root = $this->em->getRepository(Level::class)->findOneBy(['code' => 'root']);
       $this->assertCount(2, $root->getChildren());
       $l1a = $root->getChild('l1a');
       $this->assertEquals('l1a', $l1a->getCode());
       $this->assertCount(2, $l1a->getChildren());
       $root->getChild('l1a');
       $l2a = $l1a->getChild('l2a');
       $this->assertEquals('l2a', $l2a->getCode());
       $root->getChild('l1b');
       $root->getChild('l1b');
       $this->assertEquals(spl_object_hash($root), spl_object_hash(
           $l2a->getParent()
               ->getParent()));
       $l3a = $root->getChild('l1b')->getChild('l2c')->getChild('l3a');
       $l2d = $this->em->getRepository(Level::class)->findOneBy(['code' => 'l2d']);
       $this->assertCount(2, $root->getChildren());
       $this->assertEquals('l3a', $l3a->getCode());
       $this->assertEquals('root', $l3a->getParent()->getParent()->getParent()->getCode());
       $this->assertEquals('l1b', $l3a->getParent()->getParent()->getCode());
       $this->assertEquals('l2d', $l3a->getParent()->getParent()->getChild('l2d')->getCode());
       $this->assertEquals(spl_object_hash($l2d), spl_object_hash($l3a->getParent()->getParent()->getChild('l2d')));

   }

   private function createTree()
   {
       /**
        * (root)
        * (root)-(l1a)  (root)-(l1b)
        * (l1a)-(l2a)   (l1a)-(l2b)
        * (l1b)-(l2c)   (l1b)-(l2d)
        * (l2c)-(l3a)
        */
       $root = new Level('root');
       $l1a = new Level('l1a');
       $l1a->setParent($root);
       $l1b = new Level('l1b');
       $l1b->setParent($root);
       $l2a = new Level('l2a');
       $l2b = new Level('l2b');
       $l2a->setParent($l1a);
       $l2b->setParent($l1a);
       $l2c = new Level('l2c');
       $l2d = new Level('l2d');
       $l2c->setParent($l1b);
       $l2d->setParent($l1b);
       $l3a = new Level('l3a');
       $l3a->setParent($l2c);
       $this->em->persist($root);
       $this->em->flush();
       $this->em->clear();
   }
}