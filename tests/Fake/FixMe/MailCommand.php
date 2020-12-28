<?php

class MailCommand
{
    const SLEEP_SPAN = 200;
    const TYPE_NEST = true;

    public function actionIndex()
    {
        return Mail::STATUS_PROCESS;
    }
}
