<?php

class NestExtendsMailCommand extends ExtendsMailCommand
{
    const HUGA ='huga';

    public function actionIndex()
    {
        return self::TYPE_NEST;
    }
}
