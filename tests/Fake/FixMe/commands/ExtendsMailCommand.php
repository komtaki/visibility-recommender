<?php

class ExtendsMailCommand extends MailCommand
{
    const PROTECTED_OVERRIDE = false;

    public function run()
    {
        return self::PROTECTED_USE_BY_CHILD;
    }
}
