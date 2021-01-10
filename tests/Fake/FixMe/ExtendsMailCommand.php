<?php

class ExtendsMailCommand extends MailCommand
{
    const HOGE ='hoge';
    const HUGA ='huga';

    public function actionIndex()
    {
        return self::SLEEP_SPAN;
    }
}
