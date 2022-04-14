<?php


namespace Mi2\Import\Interfaces;


use Mi2\Import\Models\Batch;

interface ConventionRequiredInterface
{
    public function matchesConvention(Batch $batch);
}
