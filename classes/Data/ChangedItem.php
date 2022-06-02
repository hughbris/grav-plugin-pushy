<?php

namespace Grav\Plugin\Pushy\Data;

class ChangedItem
{
    public string $working = '';
    public string $index = '';
    public string $path = '';
    public string $title = '';
    public string $adminUrl = '';
    public string $siteUrl = '';

    /** 
     * Parse array of changed item data into ChangedItem
     * 
     * @param array{working: string, index: string, path: string} $data 
     * @param string $title If item is a page, the title of the page
     * @param string $adminUrl If item is a page, the url in Admin
     * @param string $siteUrl If item is a page, the url on local site
     */
    public function __construct(array $data, string $title = '', string $adminUrl = '', string $siteUrl = '')
    {
        foreach ($data as $key => $value) {
            $this->{$key} = $value;
        }

        $this->title = $title;
        $this->adminUrl = $adminUrl;
        $this->siteUrl = $siteUrl;
    }
}
