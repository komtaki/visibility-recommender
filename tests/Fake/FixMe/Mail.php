<?php

declare(strict_types=1);

class Mail
{
    // 状態
    const STATUS_YET = 0;
    const STATUS_PROCESS = 1;
    const STATUS_DONE = 2;
    const STATUS_CANCEL = 99;
}
