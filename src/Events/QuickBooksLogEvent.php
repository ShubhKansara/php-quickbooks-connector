<?php

namespace ShubhKansara\PhpQuickbooksConnector\Events;

class QuickBooksLogEvent
{
    public $level;
    public $message;
    public $context;

    public function __construct($level, $message, $context = [])
    {
        $this->level = $level;
        $this->message = $message;
        $this->context = $context;
    }
}
