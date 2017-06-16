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

use GraphAware\Neo4j\OGM\Tests\Integration\Models\BooleanLabel\BlogPost;

/**
 * Class BooleanLabelTest.
 *
 * @group label-it
 */
class BooleanLabelTest extends IntegrationTestCase
{
    public function setUp()
    {
        parent::setUp();
        $this->clearDb();
    }

    public function testBlogPostIsUnpublishedByDefault()
    {
        $blogpost = new BlogPost('Learn X');
        $this->persist($blogpost);
        $this->em->flush();
        $this->assertGraphExist('(b:BlogPost {title:"Learn X"})');
        $this->assertGraphNotExist('(b:Published)');
    }

    public function testBlogPostIsPublishedIfLabelSetOnCreate()
    {
        $blogpost = new BlogPost('Learn X');
        $blogpost->setPublished(true);
        $this->persist($blogpost);
        $this->em->flush();
        $this->assertGraphExist('(b:BlogPost:Published {title:"Learn X"})');
    }

    public function testBlogPostIsNotPublishedOnLoad()
    {
        $blogpost = new BlogPost('Learn X');
        $this->persist($blogpost);
        $this->em->flush();
        $this->em->clear();

        /** @var BlogPost $post */
        $post = $this->em->getRepository(BlogPost::class)->findAll()[0];
        $this->assertTrue($post->getPublished() === null || $post->getPublished() === false);
    }

    public function testBlogPostIsPublishedOnLoad()
    {
        $blogpost = new BlogPost('Learn X');
        $blogpost->setPublished(true);
        $this->persist($blogpost);
        $this->em->flush();
        $this->em->clear();

        /** @var BlogPost $post */
        $post = $this->em->getRepository(BlogPost::class)->findAll()[0];
        $this->assertTrue($post->getPublished());
    }

    public function testLabelCanBeAddedAfterCreateAndCommit()
    {
        $blogpost = new BlogPost('Learn X');
        $this->persist($blogpost);
        $this->em->flush();
        $this->assertGraphExist('(b:BlogPost {title:"Learn X"})');
        $this->assertGraphNotExist('(b:Published)');

        $blogpost->setPublished(true);
        $this->em->flush();
        $this->assertGraphExist('(b:BlogPost:Published {title:"Learn X"})');
        $this->em->flush();
        $this->assertGraphExist('(b:BlogPost:Published {title:"Learn X"})');
        $result = $this->client->run('MATCH (n:BlogPost) RETURN count(n) AS c');
        $this->assertSame(1, $result->firstRecord()->get('c'));
    }

    public function testLabelCanBeAddedAfterLoadAndCommit()
    {
        $blogpost = new BlogPost('Learn X');
        $this->persist($blogpost);
        $this->em->flush();
        $this->em->clear();

        /** @var BlogPost $post */
        $post = $this->em->getRepository(BlogPost::class)->findAll()[0];
        $this->assertTrue($post->getPublished() === null || $post->getPublished() === false);

        $post->setPublished(true);
        $this->em->flush();
        $this->assertGraphExist('(b:BlogPost:Published {title:"Learn X"})');
        $this->em->flush();
        $result = $this->client->run('MATCH (n:BlogPost) RETURN count(n) AS c');
        $this->assertSame(1, $result->firstRecord()->get('c'));
    }

    public function testLabelCanBeRemovedAfterCreateAndCommit()
    {
        $blogpost = new BlogPost('Learn X');
        $blogpost->setPublished(true);
        $this->persist($blogpost);
        $this->em->flush();
        $this->assertGraphExist('(b:BlogPost:Published {title:"Learn X"})');

        $blogpost->setPublished(false);
        $this->em->flush();
        $this->assertGraphExist('(b:BlogPost {title:"Learn X"})');
        $this->assertGraphNotExist('(b:Published)');
        $this->em->flush();
        $this->assertGraphNotExist('(b:BlogPost:Published {title:"Learn X"})');
        $result = $this->client->run('MATCH (n:BlogPost) RETURN count(n) AS c');
        $this->assertSame(1, $result->firstRecord()->get('c'));
    }

    public function testLabelCanBeRemovedAfterLoadAndCommit()
    {
        $blogpost = new BlogPost('Learn X');
        $blogpost->setPublished(true);
        $this->persist($blogpost);
        $this->em->flush();
        $this->em->clear();

        /** @var BlogPost $post */
        $post = $this->em->getRepository(BlogPost::class)->findAll()[0];
        $this->assertTrue($post->getPublished());

        $post->setPublished(false);
        $this->em->flush();
        $this->assertGraphNotExist('(b:BlogPost:Published {title:"Learn X"})');
        $this->assertGraphExist('(b:BlogPost {title:"Learn X"})');
        $this->em->flush();
        $result = $this->client->run('MATCH (n:BlogPost) RETURN count(n) AS c');
        $this->assertSame(1, $result->firstRecord()->get('c'));
    }
}
