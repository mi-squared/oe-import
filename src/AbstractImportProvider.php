<?php

namespace Mi2\Import;

use Mi2\Import\Events\ImportBootEvent;
use Mi2\Import\Events\RegisterServices;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

abstract class AbstractImportProvider
{
    protected $eventDispatcher;

    abstract public function getKey();

    abstract public function makeImporter();

    public function __construct(EventDispatcherInterface $eventDispatcher)
    {
        $this->eventDispatcher = $eventDispatcher;
        $this->boot();
    }

    public function boot()
    {
        // Listen for the importer register event so we can dynamically add our importer
        $this->eventDispatcher->addListener(RegisterServices::REGISTER, [$this, 'register']);

        // Tell the system the importer is done registering
        $importBootEvent = new ImportBootEvent($this->getKey());
        $importBootEvent = $this->eventDispatcher->dispatch(ImportBootEvent::IMPORT_BOOTED, $importBootEvent, 10);
        return $importBootEvent;
    }

    public function register(RegisterServices $registerServicesEvent)
    {
        if ($registerServicesEvent->getKey() == $this->getKey()) {
            // error_log($this->getKey() . " registered");
            $importerService = $this->makeImporter();
            $registerServicesEvent->getManager()->register($importerService);
        }
        return $registerServicesEvent;
    }
}
