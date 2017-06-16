<?php

namespace GraphAware\Neo4j\OGM\Tests\Integration;

use Doctrine\Common\Collections\ArrayCollection;
use GraphAware\Neo4j\OGM\Annotations as OGM;
use GraphAware\Neo4j\OGM\Proxy\LazyCollection;

/**
 * Class RelationshipEntityBetweenSameModelTest
 * @package GraphAware\Neo4j\OGM\Tests\Integration
 *
 * @group re-common-model
 * @group issue-106
 */
class RelationshipEntityBetweenSameModelTest extends IntegrationTestCase
{
    public function setUp()
    {
        parent::setUp();
        $this->clearDb();
    }

    public function testReWithCommonModelCanBeSaved()
    {
        $me = new SystemUser('me');
        for ($i = 0; $i < 5; ++$i) {
            $followee = new SystemUser('followee'.$i);
            $re = new Follow($me, $followee, time() + $i);
            $me->getFollowing()->add($re);
            $followee->getFollowers()->add($re);
        }
        $this->em->persist($me);
        $this->em->flush();
        for ($i = 0; $i < 5; ++$i) {
           $this->assertGraphExist('(u:User {login:"me"})-[:FOLLOWS]->(o:User {login:"followee'.$i.'"})');
        }
    }

    /**
     * @group issue-106-c
     */
    public function testUserWithReCanBeRetrieved()
    {
        $me = new SystemUser('me');
        for ($i = 0; $i < 5; ++$i) {
            $followee = new SystemUser('followee'.$i);
            $re = new Follow($me, $followee, time() + $i);
            $me->getFollowing()->add($re);
            $followee->getFollowers()->add($re);
        }
        $this->em->persist($me);
        $this->em->flush();
        $this->em->clear();

        /** @var SystemUser $u */
        $u = $this->em->getRepository(SystemUser::class)->findOneBy(['login' => 'me']);
        $this->assertEquals('me', $u->getLogin());
        $this->assertInstanceOf(LazyCollection::class, $u->getFollowing());
        $h1 = spl_object_hash($u->getFollowing());
        $this->assertInstanceOf(Follow::class, $u->getFollowing()[0]);
        $this->assertEquals($h1, spl_object_hash($u->getFollowing()));
        $this->assertEquals(5, $u->getFollowing()->count());
    }

}

/**
 *
 * @OGM\Node(label="User")
 */
class SystemUser
{
    /**
     * @var
     *
     * @OGM\GraphId()
     *
     */
    protected $id;

    /**
     * @var
     *
     * @OGM\Property(type="string")
     */
    protected $login;

    /**
     * @var ArrayCollection
     *
     * @OGM\Relationship(relationshipEntity="Follow", targetEntity="SystemUser", collection=true, mappedBy="following", type="FOLLOWS", direction="INCOMING")
     */
    protected $followers;

    /**
     * @var ArrayCollection
     *
     * @OGM\Relationship(relationshipEntity="Follow", targetEntity="SystemUser", collection=true, mappedBy="followers", type="FOLLOWS", direction="OUTGOING")
     */
    protected $following;

    public function __construct($login)
    {
        $this->login = $login;
        $this->followers = new ArrayCollection();
        $this->following = new ArrayCollection();
    }

    /**
     * @return mixed
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return mixed
     */
    public function getLogin()
    {
        return $this->login;
    }

    /**
     * @return ArrayCollection
     */
    public function getFollowers()
    {
        return $this->followers;
    }

    /**
     * @return ArrayCollection
     */
    public function getFollowing()
    {
        return $this->following;
    }
}

/**
 * Class Follow
 * @package GraphAware\Neo4j\OGM\Tests\Integration
 *
 * @OGM\RelationshipEntity(type="FOLLOWS")
 */
class Follow
{
    /**
     * @var
     *
     * @OGM\GraphId()
     */
    protected $id;

    /**
     * @var
     *
     * @OGM\StartNode(targetEntity="SystemUser")
     */
    protected $follower;

    /**
     * @var
     *
     * @OGM\EndNode(targetEntity="SystemUser")
     */
    protected $followee;

    /**
     * @var
     *
     * @OGM\Property(type="int")
     */
    protected $since;

    public function __construct(SystemUser $follower, SystemUser $followee, $since = null)
    {
        $this->follower = $follower;
        $this->followee = $followee;
        $this->since = null !== $since ? (int) $since : time();
    }

    /**
     * @return mixed
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return mixed
     */
    public function getFollower()
    {
        return $this->follower;
    }

    /**
     * @return mixed
     */
    public function getFollowee()
    {
        return $this->followee;
    }

    /**
     * @return mixed
     */
    public function getSince()
    {
        return $this->since;
    }


}