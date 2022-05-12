<?php

namespace Grav\Plugin\Pushy\Data;

use ArrayAccess;
use Countable;
use Exception;
use Iterator;
use JsonSerializable;

/**
 * Array of urls and their accompanying changedPage
 */
class ChangedPages implements ArrayAccess, Countable, Iterator, JsonSerializable
{
    /** @var array<string, ChangedPage> */
    private $container = [];

    /** @var int */
    private $index = 0;

    public function toArray(): array
    {
        $changedPages = [];

        foreach ($this->container as $url => $changedPage) {
            $changedPages[$url] = (array) $changedPage;
        }

        return $changedPages;
    }

    /**
     * @param string $url
     * @param ChangedPage $changedPage
     */
    public function offsetSet($url, $changedPage): void
    {
        $this->container[$url] = $changedPage;
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
     * @return ChangedPage
     * @throws Exception If offset does not exist
     */
    public function offsetGet($url)
    {
        if (isset($this->container[$url])) {
            return $this->container[$url];
        } else {
            throw new Exception("No changedPage for route $url");
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
     * @return ChangedPage
     */
    public function current()
    {
        $urls = array_keys($this->container);
        $changedPage = $this->container[$urls[$this->index]];

        return $changedPage;
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
        $changedPages = [];

        foreach ($this->container as $url => $changedPage) {
            $changedPages[$url] = (array) $changedPage;
        }

        return $changedPages;
    }
}
