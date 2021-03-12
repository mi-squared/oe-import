<?php


namespace Mi2\Import;


use Mi2\Import\Interfaces\ImporterServiceInterface;
use Mi2\Import\Models\Batch;
use Mi2\Import\Models\Logger;
use Mi2\Import\Traits\InteractsWithLogger;

class NullImporter implements ImporterServiceInterface
{
    use InteractsWithLogger;

    public function supports($extension)
    {
        return false;
    }

    public function setup(Batch $batch):bool
    {
        return true;
    }

    public function validateUploadFile($file)
    {
        $this->getLogger()->addMessage("Could not initialize and importer, check that file is one of the supported types (csv, jpg, jpeg, png)");
        return false;
    }

    public function validate()
    {
        $this->getLogger()->addMessage("Could not initialize and importer, check that file is one of the supported types (csv, jpg, jpeg, png)");
        return false;
    }

    public function import()
    {
        // TODO: Implement import() method.
    }
}
