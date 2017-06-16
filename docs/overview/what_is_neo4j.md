## What is a graph database ?

A graph database is a storage engine that is specialised in storing and retrieving vast networks of information. It efficiently stores data as nodes and relationships and allows high performance retrieval and querying of those structures. Properties can be added to both nodes and relationships. Nodes can be labelled by zero or more labels, relationships are always directed and named.

Graph databases are well suited for storing most kinds of domain models. In almost all domains, there are certain things connected to other things. In most other modelling approaches, the relationships between things are reduced to a single link without identity and attributes. Graph databases allow to keep the rich relationships that originate from the domain equally well-represented in the database without resorting to also modelling the relationships as "things". There is very little "impedance mismatch" when putting real-life domains into a graph database.

## Introducing Neo4j

[Neo4j](https://neo4j.com) is an open source NOSQL graph database. It is a fully transactional database (ACID) that stores data structured as graphs consisting of nodes, connected by relationships. 
Inspired by the structure of the real world, it allows for high query performance on complex data, while remaining intuitive and simple for the developer.

Neo4j is very well-established. It has been in commercial development for 15 years and in production for over 12 years. Most importantly, it has an active and contributing community surrounding it, but it also:

* has an intuitive, rich graph-oriented model for data representation. Instead of tables, rows, and columns, you work with a graph consisting of nodes, relationships, and properties.
* has a disk-based, native storage manager optimised for storing graph structures with maximum performance and scalability.
is scalable. Neo4j can handle graphs with many billions of nodes/relationships/properties on a single machine, but can also be scaled out across multiple machines for high availability.
* has a powerful graph query language called Cypher, which allows users to efficiently read/write data by expressing graph patterns.
* has a powerful traversal framework and query languages for traversing the graph.
* can be deployed as a standalone server, which is the recommended way of using Neo4j
* can be deployed as an embedded (in-process) database, giving developers access to its core Java API


In addition, Neo4j provides ACID transactions, durable persistence, concurrency control, transaction recovery, high availability, and more. Neo4j is released under a dual free software/commercial licence model.

### Querying with Cypher

Neo4j provides a graph query language called Cypher which draws from many sources. It resembles SQL clauses but is centered around matching iconic representation of patterns in the graph.

Cypher queries typically begin with a MATCH clause, which can be used to provide a way to pattern match against the graph. Match clauses can introduce new identifiers for nodes and relationships. In the WHERE clause additional filtering of the result set is applied by evaluating expressions. The RETURN clause defines which part of the query result will be available to the caller. Aggregation also happens in the return clause by using aggregation functions on some of the returned values. Sorting can happen in the ORDER BY clause and the SKIP and LIMIT parts restrict the result set to a certain window.

Here are some examples of how easy Cypher is to use (These queries work with the "Movies" data set that comes installed with Neo4j browser)


**Names and birthplaces of Actors who appeared in a Matrix movie. **

```
MATCH (movie:Movie)<-[:ACTS_IN]-(actor)
WHERE movie.title =~ 'Matrix.*'
RETURN actor.name, actor.birthplace

```

**All movie titles the user "michal" rated more than 3 stars. **

```
MATCH (user:User {login:'michal'})-[r:RATED]->(movie)
WHERE r.stars > 3
RETURN movie.title, r.stars, r.comment
```

**User michal’s friends who rated a movie more than 3 stars.** 
  
```php
MATCH (user:User {login:'micha'})-[:FRIEND]-(friend)-[r:RATED]->(movie)
WHERE r.stars > 3
RETURN friend.name, movie.title, r.stars, r.comment
```


### Learning more

The jumping off ground for learning about Neo4J is [neo4j.com](https://neo4j.com). Here is a list of other useful resources:

* The [Neo4j documentation](https://neo4j.com/docs/) introduces Neo4j and contains links to getting started guides, reference documentation and tutorials.
* The [online sandbox](https://neo4j.com/sandbox/) provides a convenient way to interact with a Neo4j instance in combination with the online tutorial.
* [Neo4J PHP Driver](https://github.com/graphaware/neo4j-php-client) and the [Bolt Protocol](http://boltprotocol.org/).
* Several [books](https://neo4j.com/books/) available for purchase and [videos](https://www.youtube.com/neo4j) to watch.
