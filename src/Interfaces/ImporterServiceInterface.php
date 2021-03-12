<?php


namespace Mi2\Import\Interfaces;


use Mi2\Import\Models\Batch;
use Mi2\Import\Models\Logger;

interface ImporterServiceInterface
{
    /**
     * returns true if the importer supports this file type
     * returns false if not supported
     *
     * @return bool
     */
    public function supports($extension);

    public function setup(Batch $batch):bool;

    public function validate();

    /**
     *
     * Pass in the upload file from the PHP files array
     *
     * @param $file
     * @return mixed
     */
    public function validateUploadFile($file);

    public function setLogger(Logger $logger);

    public function getLogger() : Logger;

    public function import();
}
