# Property converters

It is possible to convert php entity properties to a format suitable to a database node or relationship property.

A common use case is using PHP's DateTime objects and store values as timestamps in the databse.

## The Convert annotation

```php
    /**
     * @var \DateTime
     *
     * @OGM\Property()
     * @OGM\Convert(type="datetime", options={"format":"long_timestamp"})
     */
    protected $time;
```

You have to annotate your property with the `@Convert` annotation.

So far only the `datetime` converter is available, more will come during the next release.

The options available are the format and the timezone.

The format has two helper values :

* `timestamp` : equivalent of `U` in [php date format](https://secure.php.net/manual/fr/datetime.createfromformat.php)
* `long_timestamp` : equivalent's of above but multiplied by 1000, more useful if the timestamps are coming from neo4j java plugins/listeners (millis)

