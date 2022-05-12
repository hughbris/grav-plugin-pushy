<?php

namespace Grav\Plugin\Pushy\Data;

/**
 * Response to any Git action request
 */
class GitActionResponse
{
    public bool $isSuccess = false;
    public string $alert = '';

    /**
     * @param bool $isSuccess `true` if page has been committed successfully, else `false`
     * @param string $alert Message describing result of 'commitPages' request
     */
    public function __construct(bool $isSuccess, string $alert)
    {
        $this->isSuccess = $isSuccess;
        $this->alert = $alert;
    }
}
