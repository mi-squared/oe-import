<?php

/**
 * This class represents an MSS file, which is
 * a batch of updates that are overlayed onto the
 * database.
 */

namespace Mi2\Import\Models;

use Mi2\Framework\AbstractEntity;
use OpenEMR\Events\BoundFilter;

class Batch extends AbstractEntity
{
    public static $table = 'aa_import_batch';

    protected $id;
    protected $status;
    protected $user_filename;
    protected $filename;
    protected $created_datetime;
    protected $start_datetime;
    protected $end_datetime;
    protected $messages = [];

    /**
     * @return mixed
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return mixed
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * @return mixed
     */
    public function getUserFilename()
    {
        return $this->user_filename;
    }

    /**
     * @return mixed
     */
    public function getFilename()
    {
        return $this->filename;
    }

    /**
     * @return mixed
     */
    public function getCreatedDatetime()
    {
        return $this->created_datetime;
    }

    /**
     * @return mixed
     */
    public function getStartDatetime()
    {
        return $this->start_datetime;
    }

    /**
     * @return mixed
     */
    public function getEndDatetime()
    {
        return $this->end_datetime;
    }

    /**
     * @return array
     */
    public function getMessages(): array
    {
        return $this->messages;
    }


    /**
     * These are status codes for the batch table's status
     * field.
     */
    const STATUS_WAIT = 'waiting'; // Waiting to be processed
    const STATUS_PROCESSING = 'processing'; // In-progress
    const STATUS_COMPLETE = 'complete'; // Complete with no errors
    const STATUS_ERROR = 'error'; // Complete, but there was an error, like caught exception. There should be message in messages field

    /**
     *
     * Use the create method to create a new batch using an associative array
     * with database fields as keys
     *
     * @param array $fields
     * @return int
     */
    public static function create(array $fields)
    {
        return parent::create($fields); // TODO: Change the autogenerated stub
    }

    public static function find($id)
    {
        $batch_table = self::$table;
        $delta_table = Delta::$table;
        $sql = "SELECT * FROM $batch_table B WHERE B.id = ? LIMIT 1";
        $result = sqlQuery($sql, [$id]);
        return $result;
    }

    public static function delete($id)
    {
        // Delete the file on disk, then delete the record
        $batch = self::find($id);
        unlink($batch['filename']);
        Delta::deleteByBatchID($id);
        parent::delete($id);
    }

    public static function fetchByStatus($status = self::STATUS_WAIT)
    {
        $batch_table = self::$table;
        $sql = "
            SELECT B.id, B.status, B.start_datetime, B.end_datetime, B.created_datetime, B.filename, B.user_filename,
            B.error_count, B.messages
            FROM $batch_table B WHERE status = ?";
        return sqlStatement($sql, [$status]);
    }

    public static function all(BoundFilter $filter = null)
    {
        $batch_table = self::$table;

        // Build a query that selects all the batches, along with the record counts for total processed,
        // number of inserts and number of modifications
        // These field names are used in the report (see getColumns())
        $sql = "
            SELECT B.id, B.status, B.start_datetime, B.end_datetime, B.created_datetime, B.filename, B.user_filename, B.record_count,
                   (B.num_modified + B.num_inserted) AS num_total, B.num_modified, B.num_inserted, B.messages
            FROM $batch_table B";

        // error_log($sql);

        return sqlStatement($sql);
    }

    public static function getColumns()
    {
        return [
            'ID' => 'id',
            'Status' => 'status',
            'File Name' => 'user_filename',
            'Created Time' => 'created_datetime',
            'Start Time' => 'start_datetime',
            'End Time' => 'end_datetime',
            'Messages' => 'messages'
        ];
    }
}
