<?php

/**
 * Created by PhpStorm.
 * User: Sobraz
 * Date: 01/04/2017
 * Time: 15:49
 */
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