<?php

declare(strict_types=1);

class Mail
{
    // 状態
    private const STATUS_YET = 0;
    public const STATUS_PROCESS = 1;
    private const STATUS_DONE = 2;
    private const STATUS_CANCEL = 99;
}
