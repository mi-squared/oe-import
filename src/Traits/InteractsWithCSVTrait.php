<?php

namespace Mi2\Import\Traits;

use Mi2\Import\Interfaces\ColumnMapperInterface;
use Mi2\Import\Models\Batch;
use Mi2\Import\Models\Response;
use OpenEMR\Validators\ProcessingResult;

trait InteractsWithCSVTrait
{
    protected $columns = [];

    public abstract function getColumnMapper();

    public function remove_utf8_bom($text)
    {
        $bom = pack('H*','EFBBBF');
        $text = preg_replace("/^$bom/", '', $text);
        return $text;
    }

    public function escape_column_data()
    {
        $escaped = [];
        foreach ($this->columns as $column) {

            // Trim white space, remove new lines and extra spaces
            $column = trim($column);
            $column = str_replace("\n", " ", $column);
            $column = str_replace("  ", " ", $column);
            $column = str_replace("   ", " ", $column);

            // Remove byte order mark
            $column = $this->remove_utf8_bom($column);
            $escaped[] = $column;
        }
        $this->columns = $escaped;
    }

    /**
     * We only support CSV
     *
     * @param $extension
     * @return bool
     */
    public function supports($extension)
    {
        if ($extension == 'csv') {
            return true;
        }

        return false;
    }

    public function setup(Batch $batch):bool
    {
        $success= true;
        $this->batch_id = $batch->getId();
        $this->filename = $batch->getFilename();

        $this->fh_source = fopen($this->filename, 'r');
        if ($this->fh_source === false) {
            $this->getLogger()->addMessage("Failed to open " . $this->filename . " for processing.");
            $success = false;
        }

        if ($success) {
            //read a line
            $this->columns = fgetcsv($this->fh_source, 0, ',');
            $this->escape_column_data();
        }

        return $success;
    }

    public function validateUploadFile($file)
    {
        $this->fh_source = fopen($file['tmp_name'], 'r') or die("Failed to open file");

        //read a line
        $this->columns = fgetcsv($this->fh_source, 0 , ',');
        $this->escape_column_data();

        $valid = $this->validate();
        fclose($this->fh_source);
        return $valid;
    }

    public function validate()
    {
        $valid = true;
        // First make sure there are no extra columns in spreadsheet that we don't know what to do with
        $known_columns = array_keys($this->getColumnMapper()->get_column_mapping());
        foreach ($this->columns as $column) {
            // Skip columns with no header
            if ($column == "") {
                continue;
            }

            if (!in_array($column, $known_columns)) {
                $this->getLogger()->addMessage("Unknown column `$column` in file");
                $valid = false;
            }
        }

        // Then make sure all the required columns are in the spreadsheet
        foreach ($this->getColumnMapper()->get_column_mapping() as $required_column => $mapping) {
            if (
                $mapping !== null &&
                !in_array($column, $this->columns)
            ) {
                $this->getLogger()->addMessage("File missing required column `$column` in file");
                $valid = false;
            }
        }

        return $valid;
    }

    public function import()
    {
        // We already have our columns
        $record_count = 0;
        $errors = 0;
        $this->num_inserted = 0;
        $this->num_modified = 0; // We track modifications in the onPatientUpdated callback so we only include actaullly changed patients
        while (!feof($this->fh_source)) {

            //read a line
            $line = fgetcsv($this->fh_source, 0 , ',');

            // make sure the line has data
            if (false === $line) {
                continue;
            }

            // Create an assoc array with the keys bing the column headers of the sheet
            $patient_data = array_combine($this->columns, $line);
            // If we have an existing patient, update. Otherwise create.
            // This probably doesn't need to be in try/catch because nothing throws exception
            // How can we handle errors?
            try {
                $response = $this->getColumnMapper()->import_row($patient_data);
            } catch (\Exception $e) {
                $errors++;
                $this->getLogger()->addMessage($e->getMessage());
            }

            if ($response instanceof ProcessingResult) {
                if (is_array($response->getValidationMessages())) {
                    foreach ($response->getValidationMessages() as $validationMessage) {
                        foreach ($validationMessage as $key => $value) {
                            $this->getLogger()->addMessage($value);
                        }
                    }
                }
            }

            $record_count++;
        }
        fclose($this->fh_source);

        $response = new Response();
        $response->setResult(Response::SUCCESS);
        return $response;
    }
}
