<?php

namespace Grav\Plugin\Pushy\Data;

use Grav\Plugin\Pushy\GitItemStatusType;
use Grav\Plugin\Pushy\GitItemType;

class ChangedItem
{
    public string $working = '';
    public string $index = '';
    public string $path = '';
    public string $orig_path = '';
    public string $type = GitItemType::Page;
    public string $title = '';
    public string $adminUrl = '';
    public string $siteUrl = '';

    /** 
     * Parse array of changed item data into ChangedItem
     * 
     * @param array{working: string, index: string, path: string} $gitItem 
     * @param string $type The type of git item: Page, Module, Config or Other
     * @param string $title If item is a page, the title of the page
     * @param string $adminUrl If item is a page, the url in Admin
     * @param string $siteUrl If item is a page, the url on local site
     */
    public function __construct(
        array $gitItem,
        string $type = GitItemType::Page,
        string $title = '',
        string $adminUrl = '',
        string $siteUrl = ''
    ) {
        foreach ($gitItem as $key => $value) {
            $this->{$key} = $value;
        }

        $this->type = $type;
        $this->title = $title;
        $this->adminUrl = $adminUrl;
        $this->siteUrl = $siteUrl;
    }
}
