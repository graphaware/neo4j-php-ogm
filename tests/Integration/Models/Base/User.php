<?php

namespace GraphAware\Neo4j\OGM\Tests\Integration\Models\Base;

use GraphAware\Neo4j\OGM\Annotations as OGM;

/**
 * Class User.
 *
 * @package GraphAware\Neo4j\OGM\Tests\Integration\Models\Base
 *
 * @OGM\Node(label="User")
 */
class User
{
    /**
     * @OGM\GraphId()
     *
     * @var int
     */
    protected $id;

    /**
     * @OGM\Property()
     *
     * @var string
     */
    protected $login;

    /**
     * @OGM\Property()
     *
     * @var int
     */
    protected $age;

    public function __construct($login, $age = null)
    {
        $this->login = $login;
        $this->age = $age;
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
     * @return int
     */
    public function getAge()
    {
        return $this->age;
    }

    /**
     * @param string $login
     */
    public function setLogin($login)
    {
        $this->login = $login;
    }

    /**
     * @param int $age
     */
    public function setAge($age)
    {
        $this->age = $age;
    }
}
