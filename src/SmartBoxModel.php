<?php

namespace Helper;

class SmartBoxModel
{
    public function updateValue($value)
    {
        //TODO: Retrieve file from configuration file
        return file_put_contents(__DIR__ . '/../data/temp.txt', $value, LOCK_EX);
    }
}
