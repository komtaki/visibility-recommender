<?php

class NestExtendsMailCommand extends ExtendsMailCommand
{
    public function actionIndex()
    {
        return self::TYPE_NEST;
    }
}
