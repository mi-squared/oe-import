<?php

namespace Mi2\Import;

use Mi2\Import\Events\ImportBootEvent;
use Mi2\Import\Events\RegisterServices;
use Mi2\Import\Interfaces\ImporterServiceInterface;
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
        $importBootEvent = $this->eventDispatcher->dispatch($importBootEvent, ImportBootEvent::IMPORT_BOOTED,  10);
        return $importBootEvent;
    }

    public function register(RegisterServices $registerServicesEvent)
    {
        if ($registerServicesEvent->getKey() == $this->getKey()) {
            // error_log($this->getKey() . " registered");
            // Allow makeImporter() to return multiple importerServices
            $importerService = $this->makeImporter();
            if ($importerService instanceof ImporterServiceInterface) {
                $registerServicesEvent->getManager()->register($importerService);
            } else if (is_array($importerService)) {
                foreach ($importerService as $service) {
                    if ($service instanceof ImporterServiceInterface) {
                        $registerServicesEvent->getManager()->register($service);
                    }
                }
            }

        }
        return $registerServicesEvent;
    }
}
