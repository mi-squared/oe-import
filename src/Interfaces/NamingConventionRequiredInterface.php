<?php


namespace Mi2\Import\Interfaces;


interface NamingConventionRequiredInterface
{
    public function matchesConvention($filename);
}
