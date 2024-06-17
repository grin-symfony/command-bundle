<?php

namespace GS\Command\Command\UseTrait;

use Symfony\Component\Console\Command\{
    Command,
    SignalableCommandInterface
};
use Symfony\Contracts\Service\ServiceSubscriberInterface;
use GS\Command\Trait\AbstractCommandTrait;
use GS\Command\Trait\MakeLockAbleTrait;

abstract class AbstractCommandUseTrait extends Command implements
    SignalableCommandInterface,
    ServiceSubscriberInterface
{
    use AbstractCommandTrait;
    use MakeLockAbleTrait;
}
