<?php

namespace App\Exceptions;

use App\Models\TimeEntry;
use RuntimeException;

class ExistingTimerException extends RuntimeException
{
    public function __construct(public readonly TimeEntry $existing)
    {
        parent::__construct(
            "User already has an active timer on ticket #{$existing->monday_ticket_id}."
        );
    }
}
