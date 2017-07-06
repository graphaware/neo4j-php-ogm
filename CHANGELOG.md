1.0.0-RC6 (not released yet)

- Fixed a bug where findOneById was not checking class label in query ([#136](https://github.com/graphaware/neo4j-php-ogm/pull/136))

1.0.0-RC5 (2017-04-15)

- Added support for Map and Map Collections in `createQuery` result sets ([#131](https://github.com/graphaware/neo4j-php-ogm/pull/131))
- Feature: `Property Converter` ([#128](https://github.com/graphaware/neo4j-php-ogm/pull/128))
- Fixed issue when m..1 will have relationship references not managed. ([#127](https://github.com/graphaware/neo4j-php-ogm/pull/127))

1.0.0-RC4 (2017-04-09)

- Fixed incorrect hydration when RE is used between same model ([#124](https://github.com/graphaware/neo4j-php-ogm/pull/124))
  (BC : The `mappedBy` is now mandatory when using RE)
- Improvements on `EntityManager::createQuery` supporting scalar results and hydration of more than one entity types ([#125](https://github.com/graphaware/neo4j-php-ogm/pull/125))

1.0.0-RC3 (2017-04-01)

- Added a possibility to detach delete node entities via EntityManager::remove ([#111](https://github.com/graphaware/neo4j-php-ogm/pull/111))
- Fixed an issue where a simple relationship would not be managed by the uow ([#112](https://github.com/graphaware/neo4j-php-ogm/pull/112))
- Fix issue [#102](https://github.com/graphaware/neo4j-php-ogm/issues/102) Unserialize error - ([#115](https://github.com/graphaware/neo4j-php-ogm/pull/115))
- Implemented Lazy Collection for smart relationships collection lazy loading ([#116](https://github.com/graphaware/neo4j-php-ogm/pull/116))

1.0.0-RC2 (2017-03-25)

- Fixed a regression where entities in a hydrated collection were re-added when loading the inverse side ([#108](https://github.com/graphaware/neo4j-php-ogm/pull/108))
- Fixed a case where the hydrator could replace a collection by a single entity during the inversed hydration ([#109](https://github.com/graphaware/neo4j-php-ogm/pull/109))
- Added first implementation of `EntityManager::createQuery` for user-defined Cypher queries mapped to PHP entities [[#110](https://github.com/graphaware/neo4j-php-ogm/pull/110)]

1.0.0-RC1 (2017-02-27)

- BaseRepository now implements Doctrine's `ObjectRepository` and `Selectable` ([#87](https://github.com/graphaware/neo4j-php-ogm/pull/87))
- Added `SKIP` and `LIMIT` to via the `findBy` Repository method ([#86](https://github.com/graphaware/neo4j-php-ogm/pull/86))
- BC : `EntityManager::buildWithHost()` has been removed ([#98](https://github.com/graphaware/neo4j-php-ogm/pull/98))

1.0.0-beta22

- Refactored Proxisation of Relationships ([#67](https://github.com/graphaware/neo4j-php-ogm/pull/67))
- BC: criteria arguments are passed as array (#67)
- BC: `@Lazy` relationship has been removed
- BC: Direct property access do not trigger lazy loading (`$this->actors` should become `$this->getActors()`
- Fix circular reference : ([#68](https://github.com/graphaware/neo4j-php-ogm/pull/68))
- Fix proxy generation for php7 return types ([#76](https://github.com/graphaware/neo4j-php-ogm/pull/76))
- Added the possibility to add order by via the findBy method ([#84](https://github.com/graphaware/neo4j-php-ogm/pull/84))

1.0.0-beta19

- ClassMetadata implements DoctrineCommon ClassMetadata
- `getClassMetada` in EntityManager now handle node and relationship entity classes
- `@Lazy` on a non-collection relationship doesn't have a lazy effect

1.0.0-beta17

- fixed a bug where fetched RE entities were not marked as managed
- fixed a bug where fetched lazy simple relationships were not marked as managed
- lazy loaded simple relationships have their non-lazy associations marked as lazy

1.0.0-bet13,14,15, 16

- multiple bug fixes

1.0.0-beta12

- Order By on Lazy Loaded Relationship Entities
- Order By on Relationship Entities

1.0.0-beta11

- Some bug fixes with relationship entities
- Real world usage test

1.0.0-beta10

- Lazy loading RelationshipEntities

1.0.0-beta9

- `OrderBy` working with Lazy and findAll()

1.0.0-beta8

- Added `OrderBy` annotations

1.0.0-beta7

- Added proxy implementations

1.0.0-beta6

- Added lazy loading first implementation

1.0.0-beta4

- Added the possibility to define relationship direction as BOTH

1.0.0-beta3

- BC : Renamed `Manager` to `EntityManager`
- Fixed an issue with entities having multiple properties with the same relationship type

1.0.0-beta2

-  Fixed a bug where a related entity was not set on the inversed side
-  Refactored metadata reflection https://github.com/graphaware/neo4j-php-ogm/pull/2

1.0.0-beta1

- First release