<?php

/**
 * Created by PhpStorm.
 * User: Sobraz
 * Date: 01/04/2017
 * Time: 15:44
 */
class DateTimeTimestampConverter extends PropertyConverter
{

    protected function convertToDbValue($entityValue)
    {
        /** @var $entityValue \DateTime */
        return $entityValue->getTimestamp();
    }

    protected function convertToPropertyEnttityValue($dbvalue)
    {
        $datetime = new \DateTime();
        $datetime->setTimestamp($dbvalue);
        return $datetime;
    }

    protected static function getPropertyEntityType() : string
    {
        return "\\DateTime";
    }
}