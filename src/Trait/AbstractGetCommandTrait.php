<?php

namespace GS\Command\Trait;

use GS\Command\Command\AbstractCommand;

trait AbstractGetCommandTrait
{
    //###> ABSTRACT ###

    /* AbstractGetCommandTrait
        Get This Command into service and use API of this Command
    */
    abstract protected function &gsCommandGetCommandForTrait(): AbstractCommand;

    //###< ABSTRACT ###
}
