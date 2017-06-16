<?php

/*
 * This file is part of the GraphAware Neo4j PHP OGM package.
 *
 * (c) GraphAware Ltd <info@graphaware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace GraphAware\Neo4j\OGM\Tests\Proxy\Model;

use GraphAware\Neo4j\OGM\Annotations as OGM;

/**
 * @OGM\Node(label="User")
 */
class User
{
    /**
     * @var int
     *
     * @OGM\GraphId()
     */
    protected $id;

    /**
     * @var string
     *
     * @OGM\Property(type="string")
     */
    protected $login;

    /**
     * @var Profile
     *
     * @OGM\Relationship(type="HAS_PROFILE", direction="OUTGOING", targetEntity="Profile", mappedBy="user")
     */
    protected $profile;

    /**
     * @var Account
     *
     * @OGM\Relationship(type="HAS_ACCOUNT", direction="OUTGOING", targetEntity="Account", mappedBy="user")
     */
    protected $account;

    /**
     * User constructor.
     *
     * @param string $login
     */
    public function __construct($login)
    {
        $this->login = $login;
        $this->profile = new Profile($login.'@graphaware.com');
        $this->account = new Account();
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getLogin()
    {
        return $this->login;
    }

    /**
     * @return Profile
     */
    public function getProfile()
    {
        return $this->profile;
    }

    /**
     * @return Account
     */
    public function getAccount()
    {
        return $this->account;
    }
}
