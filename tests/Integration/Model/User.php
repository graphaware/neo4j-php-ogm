<?php

/*
 * This file is part of the GraphAware Neo4j PHP OGM package.
 *
 * (c) GraphAware Ltd <info@graphaware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace GraphAware\Neo4j\OGM\Tests\Integration\Model;

use Doctrine\Common\Collections\ArrayCollection;
use GraphAware\Neo4j\OGM\Annotations as OGM;
use GraphAware\Neo4j\OGM\Common\Collection;
use GraphAware\Neo4j\OGM\Tests\Integration\Model\Resource as ResourceModel;

/**
 * @OGM\Node(label="User")
 */
class User
{
    /**
     * @OGM\GraphId()
     */
    protected $id;

    /**
     * @OGM\Property(type="string")
     */
    protected $login;

    /**
     * @OGM\Relationship(targetEntity="Company", type="WORKS_AT" , direction="OUTGOING", mappedBy="employees")
     */
    protected $currentCompany;

    /**
     * @OGM\Relationship(targetEntity="User", type="FOLLOWS", direction="OUTGOING", collection=true)
     *
     * @var User[]
     */
    protected $friends;

    /**
     * @OGM\Relationship(targetEntity="User", type="IN_LOVE_WITH", direction="OUTGOING", collection=true, mappedBy="lovedBy")
     */
    protected $loves;

    /**
     * @OGM\Relationship(targetEntity="User", type="IN_LOVE_WITH", direction="INCOMING", collection=true, mappedBy="loves")
     */
    protected $lovedBy;

    /**
     * @OGM\Relationship(relationshipEntity="LivesIn", type="LIVES_IN", direction="OUTGOING", mappedBy="user")
     *
     * @var LivesIn
     */
    protected $livesIn;

    /**
     * @OGM\Label(name="Active")
     *
     * @var bool
     */
    protected $isActive;

    /**
     * @var Contribution[]|Collection
     *
     * @OGM\Relationship(relationshipEntity="Contribution", direction="OUTGOING", collection=true)
     */
    protected $contributions;

    /**
     * @OGM\Property(type="int")
     */
    protected $age;

    /**
     * @var SecurityRole[]|ArrayCollection
     *
     * @OGM\Relationship(type="HAS_ROLE", direction="OUTGOING", collection=true, mappedBy="users", targetEntity="SecurityRole")
     */
    protected $roles;

    /**
     * @var UserResource[]|ArrayCollection
     *
     * @OGM\Relationship(relationshipEntity="UserResource", direction="OUTGOING", collection=true, mappedBy="user")
     * @OGM\Lazy()
     */
    protected $userResources;

    /**
     * @var int
     *
     * @OGM\Property()
     */
    protected $updatedAt;

    public function __construct($login, $age = null)
    {
        $this->login = $login;
        $this->age = $age;
        $this->friends = new ArrayCollection();
        $this->loves = new ArrayCollection();
        $this->lovedBy = new ArrayCollection();
        $this->contributions = new Collection();
        $this->roles = new ArrayCollection();
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
     * @return mixed
     */
    public function getAge()
    {
        return $this->age;
    }

    public function setAge($age)
    {
        $this->age = (int) $age;
    }

    public function setCurrentCompany(Company $company)
    {
        $this->currentCompany = $company;
    }

    public function getCurrentCompany()
    {
        return $this->currentCompany;
    }

    /**
     * @return \Doctrine\Common\Collections\ArrayCollection|\GraphAware\Neo4j\OGM\Tests\Integration\Model\User[]
     */
    public function getFriends()
    {
        return $this->friends;
    }

    public function setInactive()
    {
        $this->isActive = false;
    }

    public function setActive()
    {
        $this->isActive = true;
    }

    /**
     * @return mixed
     */
    public function getLoves()
    {
        return $this->loves;
    }

    /**
     * @return mixed
     */
    public function getLovedBy()
    {
        return $this->lovedBy;
    }

    public function addLoves(User $user)
    {
        if (!$this->loves->contains($user)) {
            $this->loves->add($user);
        }
    }

    public function addLovedBy(User $user)
    {
        if (!$this->lovedBy->contains($user)) {
            $this->lovedBy->add($user);
        }
    }

    /**
     * @return bool
     */
    public function isActive()
    {
        return $this->isActive;
    }

    /**
     * @param \GraphAware\Neo4j\OGM\Tests\Integration\Model\LivesIn $livesIn
     */
    public function setLivesIn(LivesIn $livesIn)
    {
        $this->livesIn = $livesIn;
    }

    public function getCity()
    {
        return $this->livesIn->getCity();
    }

    public function setCity(City $city, $since = null)
    {
        $since = null !== $since ? $since : 123;
        $rel = new LivesIn($this, $city, $since);
        $this->setLivesIn($rel);
        $city->addHabitant($rel);
    }

    public function removeCity(City $city)
    {
        if ($city->getName() === $this->getCity()->getName()) {
            $this->livesIn = null;
        }
    }

    /**
     * @return mixed
     */
    public function getLivesIn()
    {
        return $this->livesIn;
    }

    /**
     * @return \GraphAware\Neo4j\OGM\Common\Collection|\GraphAware\Neo4j\OGM\Tests\Integration\Model\Contribution[]
     */
    public function getContributions()
    {
        return $this->contributions;
    }

    public function addContributionTo(Repository $repository, $score)
    {
        $contribution = new Contribution($this, $repository, $score);
        $repository->getContributions()->add($contribution);
        $this->contributions->add($contribution);
    }

    /**
     * @return \GraphAware\Neo4j\OGM\Tests\Integration\Model\SecurityRole[]
     */
    public function getRoles()
    {
        return $this->roles;
    }

    public function addRole(SecurityRole $securityRole)
    {
        $this->roles->add($securityRole);
        $securityRole->getUsers()->add($this);
    }

    /**
     * @return \Doctrine\Common\Collections\ArrayCollection|\GraphAware\Neo4j\OGM\Tests\Integration\Model\UserResource[]
     */
    public function getUserResources()
    {
        return $this->userResources;
    }

    public function addResource(ResourceModel $resource, $amount)
    {
        $userResource = new UserResource($this, $resource, $amount);
        $this->userResources->add($userResource);
        $resource->getUserResources()->add($userResource);
    }

    public function setUpdatedAt(\DateTime $dateTime)
    {
        $this->updatedAt = $dateTime->getTimestamp();
    }

    public function getUpdatedAt()
    {
        return $this->updatedAt;
    }

    /** @see \Serializable::serialize() */
    public function serialize()
    {
        return serialize([
            $this->id,
            $this->login,
            $this->age,
            // see section on salt below
            // $this->salt,
        ]);
    }

    /** @see \Serializable::unserialize() */
    public function unserialize($serialized)
    {
        list(
            $this->id,
            $this->login,
            $this->age,
            // see section on salt below
            // $this->salt
            ) = unserialize($serialized);
    }
}
