<?php

namespace ShubhKansara\PhpQuickbooksConnector\Events;

class QuickBooksEntitySynced
{
    public $entity;

    public $records;

    public function __construct(string $entity, array $records)
    {
        $this->entity = $entity;
        $this->records = $records;
    }
}
