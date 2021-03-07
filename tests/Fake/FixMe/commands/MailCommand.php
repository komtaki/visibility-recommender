<?php

class MailCommand
{
    const PROTECTED_USE_BY_SELF = true;

    const PROTECTED_USE_BY_CHILD = 200;

    const PROTECTED_USE_BY_GRAND_CHILD = true;

    public function run()
    {
        echo Mail::STATUS_PROCESS;
    }

    public function getStatus()
    {
        return static::PROTECTED_USE_BY_SELF;
    }
}