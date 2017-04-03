<?php

namespace GraphAware\Neo4j\OGM\Util\Converters;

use InvalidArgumentException;

abstract  class PropertyConverter
{

    public function getDbValue($entityValue)
    {
        $type = $this->getPropertyEntityType();
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
    protected abstract function getPropertyEntityType() : string;
}