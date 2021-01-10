<?php

class NestExtendsMailCommand extends ExtendsMailCommand
{
    protected const HUGA ='huga';

    public function actionIndex()
    {
        return self::TYPE_NEST;
    }
}
