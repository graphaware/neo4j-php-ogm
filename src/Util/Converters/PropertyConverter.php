<?php

namespace GraphAware\Neo4j\OGM\Util\Converters;

abstract  class PropertyConverter
{

    public function getDbValue($entityValue)
    {
        $type = self::getPropertyEntityType();
        if(!$entityValue instanceof $type)
            throw new InvalidArgumentException(get_class($this)." cannot convert a ".get_class($entityValue));
        return $this->convertToDbValue($entityValue);
    }

    public function getEntytiValue($dbvalue)
    {
        return $this->convertToPropertyEnttityValue($dbvalue);
    }

    protected abstract function convertToDbValue($entityValue);
    protected abstract function convertToPropertyEnttityValue($dbvalue);
    protected static abstract function getPropertyEntityType() : string;
}