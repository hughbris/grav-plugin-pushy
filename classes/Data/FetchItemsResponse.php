<?php

namespace Grav\Plugin\Pushy\Data;

/**
 * Response to request for changed items.
 */
class FetchItemsResponse
{
    /** @var ChangedItem[] */
    public array $items;
    public string $alert;
    public string $countAlert;

    /**
     * @param ChangedItem[] $items The items marked as changed in Git
     * @param string $alert Message describing result of 'fetchItems' request
     * @param string $countAlert Message describing the number of locks found
     */
    public function __construct($items, string $alert, string $countAlert) {
        $this->items = $items;
        $this->alert = $alert;
        $this->countAlert = $countAlert;
    }
}
