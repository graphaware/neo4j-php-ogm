## XML mapping

You can define same mapping as described in **Annotation mapping definition** by using **XML mapping files**.
This configuration follows similar semantics as used by **Doctrine**, so it should look familiar. 

Following documentation uses same names for XML nodes and attributes as used in **Annotation mapping**. 
In case You're do not know what i.e.: `<lazy/>` node means, please look at [Lazy Loading](01-intro.md#lazy-loading) and so on.


#### Person XML mapping
Person.ogm.xml
```xml
<?xml version="1.0" encoding="utf-8"?>
<graphaware-mapping xmlns="http://graphaware.com/schemas/ogm/graphaware-mapping"
                    xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
                    xsi:schemaLocation="http://graphaware.com/schemas/ogm/graphaware-mapping http://graphaware.com/schemas/ogm/graphaware-mapping.xsd">
    <node label="Person" 
          entity="Acme\Bundle\DemoBundle\Entity\Person"
          repository-class="Acme\Bundle\DemoBundle\Repository\PersonRepository">
        <id name="id"/>
        <property name="name" type="string"/>
        <property name="age" type="int" nullable="false">
            <label name="my-age"/>
        </property>
        <relationship name="movies" type="ACTED_IN" direction="OUTGOING" target-entity="Acme\Bundle\DemoBundle\Entity\Movie" collection="true" mapped-by="actors">
            <lazy/>
            <order-by property="name" order="DESC"/>
        </relationship>
    </node>
</graphaware-mapping>
```

#### Movie XML mapping
Movie.ogm.xml
```xml
<?xml version="1.0" encoding="utf-8"?>
<graphaware-mapping xmlns="http://graphaware.com/schemas/ogm/graphaware-mapping"
                    xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
                    xsi:schemaLocation="http://graphaware.com/schemas/ogm/graphaware-mapping http://graphaware.com/schemas/ogm/graphaware-mapping.xsd">
    <node label="Movie"
          entity="Acme\Bundle\DemoBundle\Entity\Movie"
          repository-class="Acme\Bundle\DemoBundle\Repository\MovieRepository">
        <id name="id"/>
        <property name="name" type="string" nullable="true"/>
        <relationship name="actors" type="ACTED_IN" direction="OUTGOING" target-entity="Acme\Bundle\DemoBundle\Entity\Person" collection="true"/>
    </node>
</graphaware-mapping>
```

#### Rating XML mapping
Rating.ogm.xml 

Relationship mapping is also supported in XML format:

```xml
<?xml version="1.0" encoding="utf-8"?>
<graphaware-mapping xmlns="http://graphaware.com/schemas/ogm/graphaware-mapping"
                    xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
                    xsi:schemaLocation="http://graphaware.com/schemas/ogm/graphaware-mapping http://graphaware.com/schemas/ogm/graphaware-mapping.xsd">
    <relationship type="RATED" entity="Acme\Bundle\DemoBundle\Entity\Rating">
        <id name="id"/>
        <start-node name="person" target-entity="Acme\Bundle\DemoBundle\Entity\Person"/>
        <end-node name="movie" target-entity="Acme\Bundle\DemoBundle\Entity\Movie"/>
        <property name="score" type="float"/>
    </relationship>
</graphaware-mapping>

```

## How to configure XML metadata factory

To use XML configuration files, You can use third-party libraries, or configure `XmlGraphEntityMetadataFactory`:

##### Create a instance of `SymfonyFileLocator` with configured path-namespace array:
You need to pass array of paths to namespaces as first argument
```php
$fileLocator = new SymfonyFileLocator(
    [ $bundlePath . '/Resources/graphaware' => 'Acme\\Bundle\\DemoBundle\\Entity' ],
    '.ogm.xml'
);
```
`$bundlePath` is the full pathname to Your bundle root directory. For multiple bundles just add additional array elements

##### Create a instance of `NodeEntityMetadataFactory`:
This one is simple:
```php
$metadataFactory = new NodeEntityMetadataFactory(
    new PropertyXmlMetadataFactory(),
    new RelationshipXmlMetadataFactory(),
    new IdXmlMetadataFactory()
);
```

##### Create a instance of `RelationshipEntityMetadataFactory`:
simple too:
```php
$relationshipMetadataFactory = new RelationshipEntityMetadataFactory(
    new PropertyXmlMetadataFactory(),
    new IdXmlMetadataFactory()
);
```

##### Create a instance of `XmlGraphEntityMetadataFactory`:
```php
$xmlMetadataFactory = new XmlGraphEntityMetadataFactory(
    $fileLocator,
    $metadataFactory,
    $relationshipMetadataFactory
);
```

***
Now You can inject `$xmlMetadataFactory` to Graphaware `EntityManager` constructor.

Alternatively, You can create `ChainGraphEntityMetadataFactory` and use both: **XML** and **Annotation** metadata factories:

```php
$reader = new FileCacheReader(new AnnotationReader(), $cacheDirectory, $debug);
$annotationMetadataFactory = new AnnotationGraphEntityMetadataFactory($reader);

$chainMetadataFactory = new ChainGraphEntityMetadataFactory();

$chainMetadataFactory->addMetadataFactory($annotationMetadataFactory, 0);
$chainMetadataFactory->addMetadataFactory($xmlMetadataFactory, 1);
```

Now You can inject `$chainMetadataFactory` to Graphaware `EntityManager` constructor.
