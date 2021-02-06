<?php


namespace Mi2\Import\Models;

class Response extends AbstractModel
{
    const UPDATE = 'update';
    const INSERT = 'insert';

    protected $actionPerformed;
}
