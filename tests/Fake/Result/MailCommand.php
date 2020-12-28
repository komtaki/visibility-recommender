<?php

class MailCommand
{
    protected const SLEEP_SPAN = 200;

    public function actionIndex()
    {
        return Mail::STATUS_PROCESS;
    }
}
