<?php

namespace Mi2\Import;

use Mi2\Import\Models\Batch;

class ImportService
{
    protected $file;
    protected $validationMessages = [];

    // These are for while running batches of import
    protected $current_batch_id;
    protected $messages = [];
    protected $num_inserted;
    protected $num_modified;

    protected $current_record_had_correct_pid;
    protected $config;

    public function __construct()
    {
        $this->config = include __DIR__ . "/../import-config.php";
    }

    public function execute()
    {
        // Create an instance of ImportsPatientsInterface
        $importer = null;
        if ($this->config['IMPORTER']) {
            $importerClass = $this->config['PATIENT_IMPORTER'];
            $importer = new $importerClass();
        }

        // First, find all the batches that are in 'waiting' state
        $waiting_batches = Batch::fetchByStatus(Batch::STATUS_WAIT);

        while ($batch = sqlFetchArray($waiting_batches)) {

            $this->current_batch_id = $batch['id'];
            $fh_source = fopen($batch['filename'], 'r') or die("Failed to open file");
            $fp = file($batch['filename']);
            $maxRows = count($fp);

            Batch::update($batch['id'], [
                'status' => Batch::STATUS_PROCESSING,
                'start_datetime' => date('Y-m-d H:i:s')
            ]);

            $record_count = 0;
            $errors = 0;
            $this->messages = [];
            $this->num_inserted = 0;
            $this->num_modified = 0; // We track modifications in the onPatientUpdated callback so we only include actaullly changed patients
            while (!feof($fh_source)) {

                //read a line
                $line = fgetcsv($fh_source, 0 , ',');

                // If we have an existing patient, update. Otherwise create.
                // This probably doesn't need to be in try/catch because nothing throws exception
                // How can we handle errors?
                try {
                    if ($importer instanceof ImportsPatientsInterface) {
                        $response = $importer->importPatient($line);
                    }
                } catch (\Exception $e) {
                    $errors++;
                    $this->messages[] = $e->getMessage();
                }

                $record_count++;

                //for large files we may want to see the progress of the process.  For files that have less than 100
                //rows, we get a divide by zero exception if we  update every 1% of files processed. Using the
                //mod function requires that the right hand part of the function is greater than 1.

                if (($maxRows < 100) || (($record_count % ($maxRows*.01)) == 0)){
                    Batch::update($batch['id'], [
                        'num_inserted' => $this->num_inserted,
                        'num_modified' => $this->num_modified,
                        'record_count' => $record_count,
                        'error_count' => $errors,
                        'messages' => json_encode($this->messages)
                    ]);
                }
            }

            Batch::update($batch['id'], [
                'status' => Batch::STATUS_COMPLETE,
                'error_count' => $errors,
                'record_count' => $record_count,
                'num_inserted' => $this->num_inserted,
                'num_modified' => $this->num_modified,
                'messages' => json_encode($this->messages),
                'end_datetime' => date('Y-m-d H:i:s')
            ]);
            fclose($fh_source);
        }
    }

    /**
     *
     * Set the upload file for validation and to use in creation of batch
     * @param $file
     */
    public function setUploadFile($file)
    {
        $this->file = $file;
    }

    /**
     * Do basic validation on file, like make sure columns are correct
     *
     * Returns true if valid, false OW
     *
     * @param $file
     * @return bool
     */
    public function validateFile()
    {
        // TODO Dan Check columns
        if (!empty($this->file)) {
            return true;
        } else {
            $this->validationMessages[] = "No file uploaded";
        }
        return false;
    }

    /**
     * Create a new batch entry in 'waiting' state for
     * a newly uploaded file
     *
     * @return int
     */
    public function createBatch()
    {
        // Move the tmp file to documents dir
        $directory = $GLOBALS['OE_SITE_DIR'] . DIRECTORY_SEPARATOR . 'documents' . DIRECTORY_SEPARATOR . 'imports';
        if (!file_exists($directory)) {
            if (!mkdir($directory, 0700, true)) {
                $this->validationMessages[]= xl('Unable to create document directory');
                return false;
            }
        }

        // Create the file with the current date timestamp
        $parts = pathinfo($this->file['name']);
        $filepath = $directory . DIRECTORY_SEPARATOR . date("Ymdhi") . $parts['extension'];
        if (false === move_uploaded_file($this->file['tmp_name'], $filepath)) {
            $this->validationMessages[]= xl('Unable to move uploaded file');
            return false;
        }

        return Batch::create([
            'filename' => $filepath, // The name of the file on disk
            'user_filename' => $this->file['name'], // The name of the file that was uploaded
            'created_datetime' => date('Y-m-d h:i:s'),
            'status' => Batch::STATUS_WAIT
        ]);
    }

    /**
     * @return array
     */
    public function getValidationMessages()
    {
        return $this->validationMessages;
    }

    /*
     * This function inserts the background process if it doesn't already exist
     */
    public static function insertBackgroundService()
    {
        $sql = "SELECT * FROM `background_services` WHERE `name` = ? LIMIT 1";
        $row = sqlQuery($sql,['IMPORT_SERVICE']);
        if (false === $row) {
            // The background service hasn't been created so create it.
            // Set it to run the mss_service.php script every "1 minute" we want to run it every time
            // background services run
            $sql = "INSERT INTO `background_services` (`name`, `title`, `active`, `running`, `next_run`, `execute_interval`, `function`, `require_once`, `sort_order`) VALUES
            ('IMPORT_SERVICE', 'Import Service', 1, 0, '2021-01-10 11:25:10', 1, 'start_import', '/interface/modules/custom_modules/oe-import/import_service.php', 100);";

            sqlStatement($sql);
        }
    }

    public static function change_key( $array, $old_key, $new_key ) {


        if( ! array_key_exists( $old_key, $array ) )
            return $array;

        $keys = array_keys( $array );
        $keys[ array_search( $old_key, $keys ) ] = $new_key;

        return array_combine( $keys, $array );
    }

    //this returns the count, pid, and id.
    private function ptExists($macprac){

        $row = sqlQuery("Select count(*) as count, pid as pid, id from patient_data where macPrac = ?",
            array($macprac));
        if ($row['count'] > 0) {
            return $row;

        } else{

            return false;
        }

    }
}
