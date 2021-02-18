<?php

namespace Mi2\Import\Interfaces;

interface ColumnMapperInterface
{
    public function get_db_field($column_header_name);

    public function get_column_mapping();

    public function import_row($csv_row);
}
