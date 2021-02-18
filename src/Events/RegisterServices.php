<?php
namespace Mi2\Import\Events;

use Mi2\Import\ImporterServiceInterface;
use Mi2\Import\ImportManager;
use Symfony\Component\EventDispatcher\Event;

class RegisterServices extends Event
{
    const REGISTER = 'import.register';

    protected $key;
    protected $manager;

    public function __construct($key, ImportManager $manager)
    {
        $this->key = $key;
        $this->manager = $manager;
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

    public function getManager()
    {
        return $this->manager;
    }
}
