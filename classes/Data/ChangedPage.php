<?php

namespace Grav\Plugin\Pushy\Data;

class ChangedPage
{
    public string $working = '';
    public string $index = '';
    public string $path = '';

    /** 
     * Parse array of changed page data into ChangedPage
     * @param array{change: string, page: string, notes: string} $data 
     */
    public function __construct($data)
    {
        foreach ($data as $key => $value) {
            $this->{$key} = $value;
        }

        return $this;
    }
}
