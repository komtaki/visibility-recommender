<?php

class ExtendsMailCommand extends MailCommand
{
    const HOGE ='hoge';

    public function actionIndex()
    {
        return self::SLEEP_SPAN;
    }
}
