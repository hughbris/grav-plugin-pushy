<?php

namespace Grav\Plugin\Pushy\Data;

class ChangedItem
{
    public string $working = '';
    public string $index = '';
    public string $path = '';

    /** 
     * Parse array of changed item data into ChangedItem
     * 
     * @param array{working: string, index: string, path: string} $data 
     */
    public function __construct(array $data)
    {
        foreach ($data as $key => $value) {
            $this->{$key} = $value;
        }
    }
}
