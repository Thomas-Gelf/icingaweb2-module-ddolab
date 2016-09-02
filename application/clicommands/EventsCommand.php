<?php

namespace Icinga\Module\Ddolab\Clicommands;

use Exception;
use Icinga\Application\Logger;
use Icinga\Module\Ddolab\Cli\Command;
use Icinga\Module\Ddolab\IcingaEventHandler;
use Icinga\Module\Ddolab\IcingaEventToRedisStreamer;

class EventsCommand extends Command
{
    public function processAction()
    {
        $handler = new IcingaEventHandler();
    }

    public function streamAction()
    {
        $streamer = new IcingaEventToRedisStreamer($this->api());
        $streamer->stream();
    }
}
