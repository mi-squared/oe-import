<?php

namespace Mi2\Import\Events;

use Symfony\Component\EventDispatcher\Event;

class ImportBootEvent extends Event
{
    const IMPORT_BOOTED = 'import.booted';

    protected $key;

    public function __construct($key)
    {
        $this->key = $key;
    }

    /**
     * @return mixed
     */
    public function getKey()
    {
        return $this->key;
    }

    /**
     * @param mixed $key
     */
    public function setKey($key): void
    {
        $this->key = $key;
    }
}
