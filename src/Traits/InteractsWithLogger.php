<?php


namespace Mi2\Import\Traits;


use Mi2\Import\Models\Logger;

trait InteractsWithLogger
{
    protected $logger;

    public function setLogger(Logger $logger)
    {
        $this->logger = $logger;
    }

    public function getLogger() : Logger
    {
        return $this->logger;
    }
}
