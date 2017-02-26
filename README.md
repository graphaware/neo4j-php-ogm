# GraphAware Neo4j PHP OGM

## Object Graph Mapper for Neo4j in PHP

[![Build Status](https://travis-ci.org/graphaware/neo4j-php-ogm.svg?branch=master)](https://travis-ci.org/graphaware/neo4j-php-ogm)
[![Latest Stable Version](https://poser.pugx.org/graphaware/neo4j-php-ogm/v/stable.svg)](https://packagist.org/packages/graphaware/neo4j-php-ogm)
[![Latest Unstable Version](https://poser.pugx.org/graphaware/neo4j-php-ogm/v/unstable)](https://packagist.org/packages/graphaware/neo4j-php-ogm)
[![Total Downloads](https://poser.pugx.org/graphaware/neo4j-php-ogm/downloads)](https://packagist.org/packages/graphaware/neo4j-php-ogm)
[![License](https://poser.pugx.org/graphaware/neo4j-php-ogm/license)](https://packagist.org/packages/graphaware/neo4j-php-ogm)

**Current Release** : `1.0.0-beta21`

### Basic Usage :

Storing / retrieving entities is done by declaring your entities with mapping annotations. It is very similar to the Doctrine2 ORM.

```php
<?php

namespace Demo;

use GraphAware\Neo4j\OGM\Annotations as OGM;

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
    protected $name;

    /**
     * @OGM\Property(type="int")
     */
    protected $age;


    // Getters and Setters
}
```

```php
$this->em = $this->getEntityManager();
// The entity manager is generally created somewhere else in your application and available in the dependency injection container.
// More info about the creation is in the documentation

// Creating and Persisting a User

$bart = new User('Bart Johnson', 33);
$this->em->persist($bart);
$this->em->flush();

// Retrieving from the database

$john = $this->em->getRepository(User::class)->findOneBy(['name' => 'John Doe']);
echo $john->getAge();

// Updating
$john->setAge(35);
$this->em->flush();
```

---

## Documentation

The documentation is available [here](docs/01-intro.md).

## Getting Help

For questions, please open a new thread on [StackOverflow](https://stackoverflow.com) with the `graphaware`, `neo4j` and `neo4j-php-ogm` tags.

For isses, please raise a Github issue in the repository.

## License

The library is released under the MIT License, refer to the LICENSE file bundled with this package.
