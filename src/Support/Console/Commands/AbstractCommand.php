<?php

namespace Support\Console\Commands;

use Illuminate\Console\Command;

/**
* AbstractCommand
*/
class AbstractCommand extends Command
{
    protected function getAppNamespace()
    {
        return $this->laravel->getInstance()->getNamespace();
    }
}