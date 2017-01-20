<?php

namespace Icinga\Module\Ddolab\Clicommands;

use Icinga\Application\Logger;
use Icinga\Module\Ddolab\Cli\Command;
use Icinga\Module\Ddolab\IcingaEventHandler;
use Icinga\Module\Ddolab\IcingaEventToRedisStreamer;

class EventsCommand extends Command
{
    public function processAction()
    {
        $handler = new IcingaEventHandler($this->ddo());
        $handler->processEvents();
    }

    public function streamAction()
    {
        $streamer = new IcingaEventToRedisStreamer($this->api());
        $streamer->stream();
    }
}
