<?php

class MailCommand
{
    protected const SLEEP_SPAN = 200;
    protected const TYPE_NEST = true;

    public function actionIndex()
    {
        return Mail::STATUS_PROCESS;
    }
}
