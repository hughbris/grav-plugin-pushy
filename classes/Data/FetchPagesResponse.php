<?php

namespace Grav\Plugin\Pushy\Data;

use Grav\Plugin\PageLocks\Traits\NoIndexAccessTrait;

/**
 * Response to request for changed pages.
 */
class FetchPagesResponse
{
    public ChangedPages $pages;
    public string $alert;
    public string $countAlert;

    /**
     * @param ChangedPages $pages The pages marked as changed in Git
     * @param string $alert Message describing result of 'fetchPages' request
     * @param string $countAlert Message describing the number of locks found
     */
    public function __construct(ChangedPages $pages, string $alert, string $countAlert) {
        $this->pages = $pages;
        $this->alert = $alert;
        $this->countAlert = $countAlert;
    }
}
