<?php

class NestExtendsMailCommand extends ExtendsMailCommand
{
    protected const PROTECTED_OVERRIDE =true;

    public function run()
    {
        return self::PROTECTED_USE_BY_GRAND_CHILD;
    }
}
