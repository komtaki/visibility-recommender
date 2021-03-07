<?php

declare(strict_types=1);

class Mail
{
    // not used
    const STATUS_YET = 0;
    // used by command class
    const STATUS_PROCESS = 1;
    // not used
    public const STATUS_DONE = 2;
    // used by view
    const STATUS_CANCEL = 99;
}
