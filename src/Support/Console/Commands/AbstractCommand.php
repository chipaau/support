<?php

namespace Support\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Container\Container;

/**
* AbstractCommand
*/
class AbstractCommand extends Command
{
    protected function getAppNamespace()
    {
        return Container::getInstance()->getNamespace();
    }
}