<?php

declare(strict_types=1);

class Mail
{
    // not used
    private const STATUS_YET = 0;
    // used by command class
    public const STATUS_PROCESS = 1;
    // not used
    private const STATUS_DONE = 2;
    // used by view
    public const STATUS_CANCEL = 99;
}
