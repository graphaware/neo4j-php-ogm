## GraphAware Neo4j PHP OGM - Documentation

### Introduction

Neo4j-PHP-OGM is an Object Graph Mapper for Neo4j in PHP.

It uses the RepositoryPattern and is very similar to the Doctrine2 ORM, also it makes uses of Doctrine Annotations and Collection library.

### Getting started - the Neo4j Movies Example

This getting started guide is based on the Neo4j movies example you can load by running the `:play movies` in the neo4j browser.

#### Installation

Require the OGM via composer :

```bash
composer require graphaware/neo4j-php-ogm:@rc
```

### Domain identification

Let's take a look at the movie graph and define what our domain objects will look like :

![Domain](_assets/_01-domain.png)

We can identify the following entities :

* a **Person** having a `name` and `born` properties
* a **Movie** having a `title`, `tagline` and `released` properties

Also, the following relationships can be identified :

a `Person` acted in a `Movie`
a `Person` wrote a `Movie`


### Mapping definition

Mapping definition is done by using **Annotations** on your domain object entities, 
or **XML** mapping files (XML mapping documentation can be found [here](xml-mapping.md)). Let's build the Person model :

```php
<?php

namespace Movies;

use GraphAware\Neo4j\OGM\Annotations as OGM;

/**
 * @OGM\Node(label="Person")
 */
class Person
{
    /**
     * @OGM\GraphId()
     * @var int
     */
    protected $id;

    /**
     * @OGM\Property(type="string")
     * @var string
     */
    protected $name;

    /**
     * @OGM\Property(type="int")
     * @var int
     */
    protected $born;

    /**
     * @param string $name
     * @param int|null $born
     */
    public function __construct($name, $born = null)
    {
        $this->name = $name;
        $this->born = $born;
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
    public function getName()
    {
        return $this->name;
    }

    /**
     * @return int
     */
    public function getBorn()
    {
        return $this->born;
    }
}
```

##### Node

First off, you'll need to import the `GraphAware\Neo4j\OGM\Annotations` directory with the `use` statement.

Secondly, you'll need to declare your model as a graph entity, by adding the `@OGM\Node()` annotation on the class.

The `@OGM\Node()` annotation must contain the name of the label representing the person nodes in the database.


##### GraphId

The `@OGM\GraphId` annotation defines the property on which the internal neo4j node id will be mapped. This property and annotation
is mandatory.

As of now, the only allowed property name is `id`, in the future you'll be able to specify a custom property name.

##### Property

The `@OGM\Property` annotation defines which entity properties will be managed by the OGM. You can have properties without this
annotation and they will not be saved / loaded to / from the database.

The type argument defines the internal type (php) of the property, common types are `string`, `int`, `float`, ...

Currently, the exact property name used in your domain model is used as property key on the database node. (This will evolve).

### Entity EntityManager

As of now, we are able to load / save `Person` entities to the database, as well as handling updates. Before we need to create
the entity manager which will be the central point of operations.

Creating the manager is just instantiating a new `GraphAware\Neo4j\OGM\EntityManager` object and passing your neo4j host url :

```php
use GraphAware\Neo4j\OGM\EntityManager;

$manager = EntityManager::create('http://localhost:7474');
```

#### Repository

Finding nodes from the database is done via their repository, retrieving the corresponding repository is done by passing the
entity class name to the `getRepository` method :

```php
use GraphAware\Neo4j\OGM\EntityManager;
use Movies\Person;

$manager = EntityManager::create('http://localhost:7474');

$personRepository = $manager->getRepository(Person::class);
```

Once you have the repository, you can retrieve node from the database, let's find `Tom Hanks` :

```php
use GraphAware\Neo4j\OGM\EntityManager;
use Movies\Person;

$manager = EntityManager::create('http://localhost:7474');

$personRepository = $manager->getRepository(Person::class);
$tomHanks = $personRepository->findOneBy(['name' => 'Tom Hanks']);
```

The available methods on the repository are :

* `findAll()`
* `findOneBy($propertyKey, $propertyValue)`
* `findBy($property, $propertyValue)`
* `findOneById($id)`

#### Persisting new objects

Persistence is handled by the OGM with two main methods, `persist()` and `flush()`.

To briefly summarize the difference, the objects you pass to the `persist` method become `managed` by the Entity EntityManager,
keeping track of their changes and reflecting the changes at the next `flush()` operation.

Let's create a new actor, named `Kevin Ross` and born in `1976` :

```php
$actor = new Person('Kevin Ross', 1976);
$manager->persist($actor);
$manager->flush();
```

And verify our database :

![New entity persisted](_assets/_02-newactor.png)

The entity remains to be managed by the Entity EntityManager, this means that any update to your object will be reflected on next flush.
This is also the case when you load entities from the database, they become automtically managed, let's modify Tom Hank's year of birth :

```php
// adding the setter to the model

    /**
     * @param int $year
     */
    public function setBorn($year)
    {
        $this->born = $year;
    }
```

```php
$tomHanks->setBorn(1990);
$manager->flush();
```

![Update entity](_assets/_03-updatenode.png)

He is quite younger now :)

For finishing this first part, let's create our Movie entity before opening the chapter of relationships :

```php
<?php

namespace Movies;

use GraphAware\Neo4j\OGM\Annotations as OGM;

/**
 * @OGM\Node(label="Movie")
 */
class Movie
{
    /**
     * @OGM\GraphId()
     * @var int
     */
    protected $id;

    /**
     * @OGM\Property(type="string")
     * @var string
     */
    protected $title;

    /**
     * @OGM\Property(type="string")
     * @var string
     */
    protected $tagline;

    /**
     * @OGM\Property(type="int")
     * @var int
     */
    protected $released;

    /**
     * @param string $title
     * @param string|null $released
     */
    public function __construct($title, $released = null)
    {
        $this->title = $title;
        $this->released = $released;
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
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * @return string
     */
    public function getTagline()
    {
        return $this->tagline;
    }

    /**
     * @param string $tagline
     */
    public function setTagline($tagline)
    {
        $this->tagline = $tagline;
    }

    /**
     * @return int
     */
    public function getReleased()
    {
        return $this->released;
    }
}
```

---

### Managing relationships

Mapping relationship to an domain object property is done with the `@OGM\Relationship` annotation. There are two types of relationships managed
by the OGM.

* Simple relationships, where the property will reflect another node
* Relationships entities, where the property will reflect a `RelationshipEntity` mapped object.

The first one is generally used for relationships where you don't have properties or don't need them in your domain model.

The latter is used when you need to filter on the relationships and need them in your business logic.

An example of a simple relationship can be a `FOLLOWS` relationship while a `RANKED` relationship with a score property is better
handled by a RelationshipEntity.

Let's add the `ACTED_IN` relationship to our Person model, this will be a simple relationship :

```php
<?php

namespace Movies;

use Doctrine\Common\Collections\ArrayCollection;
use GraphAware\Neo4j\OGM\Annotations as OGM;

/**
 * @OGM\Node(label="Person")
 */
class Person
{
    /...

    /**
     * @OGM\Relationship(type="ACTED_IN", direction="OUTGOING", targetEntity="Movie", collection=true)
     * @var ArrayCollection|Movie[]
     */
    protected $movies;

    /**
     * @param string $name
     * @param int|null $born
     */
    public function __construct($name, $born = null)
    {
        $this->name = $name;
        $this->born = $born;
        $this->movies = new ArrayCollection();
    }

    ...

    /**
     * @return \Doctrine\Common\Collections\ArrayCollection|\Movies\Movie[]
     */
    public function getMovies()
    {
        return $this->movies;
    }

    /**
     * @param \Movies\Movie $movie
     */
    public function addMovie(Movie $movie)
    {
        if (!$this->movies->contains($movie)) {
            $this->movies->add($movie);
        }
    }

    /**
     * @param \Movies\Movie $movie
     */
    public function removeMovie(Movie $movie)
    {
        if ($this->movies->contains($movie)) {
            $this->movies->removeElement($movie);
        }
    }
}
```


Let's explain the annotation parameters :

```php
    /**
     * @OGM\Relationship(type="ACTED_IN", direction="OUTGOING", targetEntity="Movie", collection=true)
     * @var ArrayCollection|Movie[]
     */
    protected $movies;
```

* `type` is the relationship type
* `direction`  is the direction of the relationship, can be of `OUTGOING`, `INCOMING` or `BOTH`
* `targetEntity` defines the classname of the entity representing the node on the other side of the relationship.
* `collection` defines whether or not there can be multiple relationships of the same type connected to this entity.

Note : `targetEntity` takes the **fully qualified class name** as argument, you can pass only the classname if both of the
entities lives in the same namespace.

Simply with this annotation, the nodes connected by an outgoing `ACTED_IN` relationship to this entity will be returned, let's
take Tom Hanks again and all his movies :

```php
$tomHanks = $manager->getRepository(Person::class)->findOneBy(['name' => 'Tom Hanks']);
echo sprintf('Tom Hanks played in %d movies', count($tomHanks->getMovies())) . PHP_EOL;

foreach ($tomHanks->getMovies() as $movie) {
    echo $movie->getTitle() . PHP_EOL;
}
```

```text
$ php app.php
Tom Hanks played in 12 movies
Charlie Wilson's War
The Polar Express
A League of Their Own
Cast Away
Apollo 13
The Green Mile
The Da Vinci Code
Cloud Atlas
That Thing You Do
Joe Versus the Volcano
You've Got Mail
Sleepless in Seattle

```

Great, we can retrieve the related movies for an actor, but once we own the Movie object, there is no way to retrieve back the actor, let's tackle
this by adding the appropriate mapping to the Movie domain object class.

```php
<?php

namespace Movies;

use Doctrine\Common\Collections\ArrayCollection;
use GraphAware\Neo4j\OGM\Annotations as OGM;

/**
 * @OGM\Node(label="Movie")
 */
class Movie
{
    ...

    /**
     * @OGM\Relationship(type="ACTED_IN", direction="INCOMING", targetEntity="Person", collection=true)
     * @var ArrayCollection|Person[]
     */
    protected $actors;

    /**
     * @param string $title
     * @param string|null $released
     */
    public function __construct($title, $released = null)
    {
        $this->title = $title;
        $this->released = $released;
        $this->actors = new ArrayCollection();
    }

    ...

    /**
     * @return \Doctrine\Common\Collections\ArrayCollection|\Movies\Person[]
     */
    public function getActors()
    {
        return $this->actors;
    }

    /**
     * @param \Movies\Person $person
     */
    public function addActor(Person $person)
    {
        if (!$this->actors->contains($person)) {
            $this->actors->add($person);
        }
    }

    /**
     * @param \Movies\Person $person
     */
    public function removeActor(Person $person)
    {
        if ($this->actors->contains($person)) {
            $this->actors->removeElement($person);
        }
    }
}
```

We need also to add a parameter to the `Person` entity mapping for the `movies` relationship annotation.

```php
    /**
     * @OGM\Relationship(type="ACTED_IN", direction="OUTGOING", targetEntity="Movie", collection=true, mappedBy="actors")
     * @var ArrayCollection|Movie[]
     */
    protected $movies;
```

The `mappedBy` argument defines the name of the property of the other entity where the relationship is bound to. This is to ensure
that the relationships are mapped to the right property, maybe the Movie entity will have incoming relationships from other objects
than a Person.

Let's modify the related `Cast Away` movie related to Tom Hanks to a new `Cast Away 2` movie name.

```php
// Find Tom Hanks, filter his movies to find Cast Away and rename it to Cast Away 2
/** @var Person $tomHanks */
$tomHanks = $manager->getRepository(Person::class)->findOneBy(['name' => 'Tom Hanks']);
$filter = array_values(array_filter($tomHanks->getMovies()->toArray(), function(\Movies\Movie $movie) {
    return 'Cast Away' === $movie->getTitle();
}));

/** @var \Movies\Movie $castAway */
$castAway = $filter[0];
$castAway->setTitle('Cast Away 2');
$manager->flush();
```

And verify our database :

![update_related](_assets/_04-updaterelated.png)

All good !

---

### Relationship Entities

For the sake of the example, we will create another entity, called `User` that will represent a user visiting the movies application
and rating the movies he saw :

```php
<?php

namespace Movies;

use GraphAware\Neo4j\OGM\Annotations as OGM;

/**
 * @OGM\Node(label="User")
 */
class User
{
    /**
     * @OGM\GraphId()
     * @var int
     */
    protected $id;

    /**
     * @OGM\Property(type="string")
     * @var string
     */
    protected $login;

    /**
     * @param string $login
     */
    public function __construct($login)
    {
        $this->login = $login;
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
}
```

In order to relate the user and the movie with a `RATED` relationship having a score property, we need a different type of object, where
we could use actually this relationship as first class citizen in our application.

This type of object is called a `RelationshipEntity`, let's create it :

```php
<?php

namespace Movies;

use GraphAware\Neo4j\OGM\Annotations as OGM;

/**
 * @OGM\RelationshipEntity(type="RATED")
 */
class Rating
{
    /**
     * @OGM\GraphId()
     * @var int
     */
    protected $id;

    /**
     * @OGM\StartNode(targetEntity="User")
     * @var User
     */
    protected $user;

    /**
     * @OGM\EndNode(targetEntity="Movie")
     * @var Movie
     */
    protected $movie;

    /**
     * @OGM\Property(type="float")
     * @var float
     */
    protected $score;

    /**
     * Rating constructor.
     * @param \Movies\User $user
     * @param \Movies\Movie $movie
     * @param float $score
     */
    public function __construct(User $user, Movie $movie, $score)
    {
        $this->user = $user;
        $this->movie = $movie;
        $this->score = $score;
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return User
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * @return Movie
     */
    public function getMovie()
    {
        return $this->movie;
    }

    /**
     * @return float
     */
    public function getScore()
    {
        return $this->score;
    }
}
```

Let's also add the corresponding annotation in the User class :

```php

use Doctrine\Common\Collections\ArrayCollection;

...

class User
{
    ...

    /**
     * @OGM\Relationship(relationshipEntity="Rating", type="RATED", direction="OUTGOING", collection=true)
     * @var Rating[]|ArrayCollection
     */
    protected $ratings;

    /**
     * @param string $login
     */
    public function __construct($login)
    {
        $this->login = $login;
        $this->ratings = new ArrayCollection();
    }

    ...

    /**
     * @return \Doctrine\Common\Collections\ArrayCollection|\Movies\Rating[]
     */
    public function getRatings()
    {
        return $this->ratings;
    }

    /**
     * @param \Movies\Movie $movie
     * @param float $score
     */
    public function rateMovie(Movie $movie, $score)
    {
        $this->ratings->add(new Rating($this, $movie, $score));
    }
```

Now let's create a new User, find 'The Matrix' movie and create a rating :

```php
$user = new User('cypher666');
/** @var Movie $movie */
$movie = $manager->getRepository(Movie::class)->findOneBy(['title' => 'The Matrix']);
$user->rateMovie($movie, '4.5');
$manager->persist($user);
$manager->flush();
```

And check our graph :

![rel-entity](_assets/_05-re.png)


### Ordering related entities

For now, you can use the `OrderBy` annotation on simple relationships to order them based on an inversed entity property

Example :

```php
<?php

namespace GraphAware\Neo4j\OGM\Tests\Integration\Model;

use Doctrine\Common\Collections\ArrayCollection;
use GraphAware\Neo4j\OGM\Annotations as OGM;

/**
 * @OGM\Node(label="Movie")
 */
class Movie
{
    /**
     * @OGM\GraphId()
     */
    public $id;

    // ...

    /**
     * @OGM\Relationship(targetEntity="Person", type="PLAYED_IN", direction="INCOMING", collection=true, mappedBy="movies")
     * @OGM\OrderBy(property="name", order="ASC")
     */
    public $players;

    public function __construct($title)
    {
        // ...
        $this->players = new ArrayCollection();
    }

    /**
     * @return mixed
     */
    public function getPlayers()
    {
        return $this->players;
    }
}
```

This will order players based on their name property.

## Removing entities

The EntityManager offers a `remove()` method to be used for removing entities from the graph.

Node objects as well as RelationshipEntity objects can be passed to this method.

```php
$guest->setRating(null);
$hotel->setRating(null);
$this->em->remove($rating);
$this->em->flush();
```
