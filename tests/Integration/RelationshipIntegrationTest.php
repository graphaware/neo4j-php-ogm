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

use GraphAware\Neo4j\OGM\Repository\BaseRepository;
use GraphAware\Neo4j\OGM\Tests\Integration\Model\Company;
use GraphAware\Neo4j\OGM\Tests\Integration\Model\Movie;
use GraphAware\Neo4j\OGM\Tests\Integration\Model\Person;
use GraphAware\Neo4j\OGM\Tests\Integration\Model\Tweeto;
use GraphAware\Neo4j\OGM\Tests\Integration\Model\User;

/**
 * Class RelationshipIntegrationTest.
 *
 * @group rel-it
 */
class RelationshipIntegrationTest extends IntegrationTestCase
{
    public function setUp()
    {
        parent::setUp();
        $this->clearDb();
    }

    public function testEntityIsPersistedWithRelationship()
    {
        $company = new Company('Acme');
        $user = new User('ikwattro');
        $user->setCurrentCompany($company);
        $company->addEmployee($user);
        $this->em->persist($user);
        $this->em->flush();
        $this->assertGraphExist('(u:User {login:"ikwattro"})-[r:WORKS_AT]->(c:Company {name:"Acme"})');
    }

    public function testEntityIsPersistedWhenOnlyOnOneSide()
    {
        $company = new Company('Acme');
        $user = new User('ikwattro');
        $company->addEmployee($user);
        $this->em->persist($company);
        $this->em->flush();
        $this->assertGraphExist('(u:User {login:"ikwattro"})-[r:WORKS_AT]->(c:Company {name:"Acme"})');
    }

    /**
     * @throws \Exception
     *
     * @group rel-it-fetch
     */
    public function testRelatedEntitiesAreFetched()
    {
        $company = new Company('Acme');
        $user = new User('ikwattro');
        $company->addEmployee($user);
        $this->em->persist($company);
        $this->em->flush();
        $this->em->clear();

        $repo = $this->em->getRepository(User::class);
        $user = $repo->findOneBy('login', 'ikwattro');
        $this->assertInstanceOf(Company::class, $user->getCurrentCompany());
    }

    /**
     * @group rel-ref
     */
    public function testRelatedEntitiesCanBeRemoved()
    {
        $user = new User('ikwattro');
        $user2 = new User('alenegro81');
        $user3 = new User('jexp');
        $user->getFriends()->add($user2);
        $user->getFriends()->add($user3);
        $this->em->persist($user);
        $this->em->flush();
        $this->assertGraphExist('(u:User {login:"ikwattro"})-[r:FOLLOWS]->(o:User {login:"alenegro81"})');
        $this->assertGraphExist('(u:User {login:"ikwattro"})-[r:FOLLOWS]->(o:User {login:"jexp"})');
        $user->getFriends()->removeElement($user2);
        $this->em->persist($user);
        $this->em->flush();
        $this->assertGraphNotExist('(u:User {login:"ikwattro"})-[r:FOLLOWS]->(o:User {login:"alenegro81"})');
        $this->em->clear();
        $ikwattro = $this->em->getRepository(User::class)->findOneBy('login', 'ikwattro');
        $this->assertCount(1, $ikwattro->getFriends());
    }

    /**
     * @group rel-ref-fetch
     */
    public function testRelatedEntitiesFetchedCanBeRemoved()
    {
        $user = new User('ikwattro');
        $user2 = new User('jexp');
        $user3 = new User('michal');
        $user->getFriends()->add($user2);
        $user->getFriends()->add($user3);
        $this->em->persist($user);
        $this->em->flush();
        $this->assertGraphExist('(u:User {login:"ikwattro"})-[r:FOLLOWS]->(o:User {login:"michal"})');
        $this->assertGraphExist('(u:User {login:"ikwattro"})-[r:FOLLOWS]->(o:User {login:"jexp"})');
        $this->em->clear();
        /** @var User $ikwattro */
        $ikwattro = $this->em->getRepository(User::class)->findOneBy('login', 'ikwattro');
        $this->assertInstanceOf(User::class, $ikwattro);
        $this->assertCount(2, $ikwattro->getFriends());
        foreach ($ikwattro->getFriends() as $friend) {
            if ($friend->getLogin() === 'jexp') {
                $ikwattro->getFriends()->removeElement($friend);
            }
        }
        $friends = $ikwattro->getFriends();
        $this->assertCount(1, $friends);
        $this->em->flush();
        $this->assertGraphNotExist('(u:User {login:"ikwattro"})-[r:FOLLOWS]->(o:User {login:"jexp"})');
    }

    /**
     * @group rel-ref-by-id
     */
    public function testRelatedEntitiesAreCorrectlyFetchedWhenRootEntityIsFoundById()
    {
        $this->playMovies();
        $id = $this->client->run('MATCH (m:Movie) WHERE m.title = "The Matrix" RETURN id(m) as id')->firstRecord()->get('id');
        /** @var Movie $movie */
        $movie = $this->em->getRepository(Movie::class)->findOneById($id);
        $this->assertEquals($id, $movie->id);
        foreach ($movie->getActors() as $actor) {
            $this->assertInstanceOf(Person::class, $actor);
        }
    }

    /**
     * @group multiple-rels-same
     */
    public function testMultipleRelationshipTypesWithSameName()
    {
        $this->clearDb();
        $user1 = new User('user1');
        $user2 = new User('user2');
        $user3 = new User('user3');
        $user4 = new User('user4');
        $user5 = new User('user5');
        $user6 = new User('user6');

        $user1->addLoves($user2);
        $user1->addLoves($user3);

        $user6->addLovedBy($user4);
        $user6->addLovedBy($user5);

        $this->em->persist($user1);
        $this->em->persist($user6);
        $this->em->flush();
        $this->em->clear();
        $this->assertGraphExist('(u2:User {login:"user2"})<-[:IN_LOVE_WITH]-(u1:User {login: "user1"})-[:IN_LOVE_WITH]->(u3:User {login: "user3"})');
        $this->assertGraphExist('(u4:User {login:"user4"})-[:IN_LOVE_WITH]->(u6:User {login:"user6"})<-[:IN_LOVE_WITH]-(u5:User {login:"user5"})');

        /** @var BaseRepository $repository */
        $repository = $this->em->getRepository(User::class);
        /** @var User $user */
        $user = $repository->findOneBy('login', 'user1');
        $this->assertCount(2, $user->getLoves());
        foreach ($user->getLoves() as $loved) {
            $this->assertCount(1, $loved->getLovedBy());
            $this->assertEquals('user1', $loved->getLovedBy()[0]->getLogin());
        }
        //$this->em->clear();
        /** @var User $u6 */
        $u6 = $repository->findOneBy('login', 'user6');
        $this->assertCount(2, $u6->getLovedBy());
        foreach ($u6->getLovedBy() as $lover) {
            $this->assertTrue($lover->getLoves()->contains($u6));
        }
    }

    /**
     * @group rels-type-multiple-single
     */
    public function testMultipleRelTypesWithSameNameNonCollection()
    {
        $this->clearDb();
        $tw1 = new Tweeto('tw1');
        $tw2 = new Tweeto('tw2');
        $tw3 = new Tweeto('tw3');
        $tw2->setFollowed($tw1);
        $tw2->setFollows($tw3);
        $this->em->persist($tw2);
        $this->em->flush();
        $this->em->clear();
        $this->assertGraphExist('(t1:Tweeto {name:"tw1"})-[:FOLLOWS]->(t2:Tweeto {name:"tw2"})-[:FOLLOWS]->(t3:Tweeto {name:"tw3"})');

        $tweetoRep = $this->em->getRepository(Tweeto::class);
        /** @var Tweeto $tweeto */
        $tweeto = $tweetoRep->findOneBy('name', 'tw2');
        $this->assertInstanceOf(Tweeto::class, $tweeto->getFollowed());
        $this->assertEquals('tw1', $tweeto->getFollowed()->getName());
        $this->assertEquals('tw3', $tweeto->getFollows()->getName());
    }

    public function testNonCollectionRelationshipsUpdated()
    {
        $this->clearDb();
        $tw1 = new Tweeto('tw1');
        $tw2 = new Tweeto('tw2');
        $tw3 = new Tweeto('tw3');
        $tw1->setFollows($tw2);
        $this->em->persist($tw1);
        $this->em->persist($tw3);
        $this->em->flush();

        $this->assertGraphExist('(t1:Tweeto {name:"tw1"})-[:FOLLOWS]->(t2:Tweeto {name:"tw2"})');

        $tw1->setFollows($tw3);
        $this->em->flush();
        $this->em->clear();

        $this->assertGraphExist('(t1:Tweeto {name:"tw1"})-[:FOLLOWS]->(t3:Tweeto {name:"tw3"})');
        $this->assertGraphNotExist('(t1:Tweeto {name:"tw1"})-[:FOLLOWS]->(t2:Tweeto {name:"tw2"})');
    }

    /**
     * @throws \Exception
     * @group relup
     */
    public function testCollectionRelationshipsUpdated()
    {
        $this->clearDb();
        $user1 = new User('user1');
        $user2 = new User('user2');
        $user3 = new User('user3');
        $this->em->persist($user3);

        $company = new Company('company');
        $company->addEmployee($user1);
        $company->addEmployee($user2);
        $this->em->persist($company);
        $this->em->flush();

        $companyRep = $this->em->getRepository(Company::class);

        $getLogins = function (&$value, $key) {
            $value = $value->getLogin();
        };

        $users = $companyRep->findAll()[0]->getEmployees()->toArray();
        array_walk($users, $getLogins);
        sort($users);
        $this->assertEquals(['user1', 'user2'], $users);

        $company->removeEmployee($user2);
        $company->addEmployee($user3);
        $this->em->flush();

        $users = $companyRep->findAll()[0]->getEmployees()->toArray();
        array_walk($users, $getLogins);
        sort($users);
        $this->assertEquals(['user1', 'user3'], $users);
    }

    /**
     * @group proxy
     */
    public function testInversedRelationshipCollectionsHydrateNonManagedRelationships()
    {
        $this->clearDb();
        $user1 = new User('u1');
        $user2 = new User('u2');
        $user3 = new User('u3');
        $company = new Company('Acme');
        $company->addEmployee($user1);
        $company->addEmployee($user2);
        $company->addEmployee($user3);
        $this->em->persist($company);
        $this->em->flush();
        $this->em->clear();

        /** @var User $u1 */
        $u1 = $this->em->getRepository(User::class)->findOneBy('login', 'u1');
        /** @var Company $comp */
        $comp = $u1->getCurrentCompany();
        $this->assertEquals('Acme', $comp->getName());
        $this->assertCount(3, $comp->getEmployees());
    }

    public function testHydratedNonCollectionRelationshipsManaged()
    {
        $this->clearDb();
        $tw1 = new Tweeto('tw1');
        $tw2 = new Tweeto('tw2');
        $tw3 = new Tweeto('tw3');
        $tw1->setFollows($tw2);
        $this->em->persist($tw1);
        $this->em->persist($tw3);
        $this->em->flush();
        $this->em->clear();
        $this->assertGraphExist('(t1:Tweeto {name:"tw1"})-[:FOLLOWS]->(t2:Tweeto {name:"tw2"})');

        $tweetoRep = $this->em->getRepository(Tweeto::class);
        /** @var Tweeto $tw1 */
        $tw1 = $tweetoRep->findOneBy('name', 'tw1');
        /** @var Tweeto $tw3 */
        $tw3 = $tweetoRep->findOneBy('name', 'tw3');
        $tw1->setFollows($tw3);
        $this->em->flush();
        $this->em->clear();
        $this->assertGraphNotExist('(t1:Tweeto {name:"tw1"})-[:FOLLOWS]->(t2:Tweeto {name:"tw2"})');
        $this->assertGraphExist('(t1:Tweeto {name:"tw1"})-[:FOLLOWS]->(t3:Tweeto {name:"tw3"})');
    }
}
