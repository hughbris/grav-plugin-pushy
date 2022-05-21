<?php

namespace Grav\Plugin\Pushy\Data;

use ArrayAccess;
use Countable;
use Exception;
use Iterator;
use JsonSerializable;

/**
 * Array of urls and their accompanying changedItems
 * 
 * @implements \ArrayAccess<string, ChangedItem>
 * @implements \Iterator<string, ChangedItem>
 */
class ChangedItems implements ArrayAccess, Countable, Iterator, JsonSerializable
{
    /** @var array<string, ChangedItem> */
    private $container = [];

    /** @var int */
    private $index = 0;

    /**
     * @return array<string, array{working: string, index: string, path: string}>
     */
    public function toArray(): array
    {
        $changedItems = [];

        foreach ($this->container as $url => $changedItem) {
            $changedItems[$url] = (array) $changedItem;
        }

        return $changedItems;
    }

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

    /*
     * Implementation of Countable
     */

    /**
     * @return int
     */
    public function count(): int
    {
        return count($this->container);
    }

    /*
     * Implementation of Iterator
     */

    /**
     * @return void
     */
    public function rewind(): void
    {
        $this->index = 0;
    }

    /**
     * @return ChangedItem
     */
    public function current()
    {
        $urls = array_keys($this->container);
        $changedItem = $this->container[$urls[$this->index]];

        return $changedItem;
    }

    /**
     * @return string
     */
    public function key()
    {
        $urls = array_keys($this->container);
        $url = $urls[$this->index];

        return $url;
    }

    public function next(): void
    {
        $this->index++;
    }

    /**
     * $return bool
     */
    public function valid(): bool
    {
        $urls = array_keys($this->container);
        $isValid = isset($urls[$this->index]);

        return $isValid;
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
