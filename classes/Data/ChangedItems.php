<?php

namespace Grav\Plugin\Pushy\Data;

use ArrayAccess;
use Exception;
use JsonSerializable;

/**
 * Array of urls and their accompanying changedItems
 * 
 * @implements \ArrayAccess<string, ChangedItem>
 */
class ChangedItems implements ArrayAccess, JsonSerializable
{
    /** @var array<string, ChangedItem> */
    private $container = [];

    /**
     * @param string $url
     * @param ChangedItem $changedItem
     */
    public function offsetSet($url, $changedItem): void
    {
        $this->container[$url] = $changedItem;
    }

    /**
     * @param mixed $url
     * @return bool
     */
    public function offsetExists($url): bool
    {
        return isset($this->container[$url]);
    }

    /**
     * @param string $url
     */
    public function offsetUnset($url): void
    {
        unset($this->container[$url]);
    }

    /**
     * @param string $url
     * @return ChangedItem
     * @throws Exception If offset does not exist
     */
    public function offsetGet($url)
    {
        if (isset($this->container[$url])) {
            return $this->container[$url];
        } else {
            throw new Exception("No changedItem for route $url");
        }
    }

    public function jsonSerialize()
    {
        $changedItems = [];

        foreach ($this->container as $url => $changedItem) {
            $changedItems[$url] = (array) $changedItem;
        }

        return $changedItems;
    }
}
