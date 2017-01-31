<?php

namespace Icinga\Module\Ddolab\Clicommands;

use Icinga\Module\Ddolab\Cli\Command;
use Icinga\Module\Ddolab\IcingaEventHandler;
use Icinga\Module\Ddolab\IcingaEventToRedisStreamer;

class EventsCommand extends Command
{
    public function processAction()
    {
        cli_set_process_title('ddolab events process');
        $handler = new IcingaEventHandler($this->ddo());
        $handler->processEvents();
    }

    public function streamAction()
    {
        cli_set_process_title('ddolab events stream');
        $streamer = new IcingaEventToRedisStreamer($this->api());
        $streamer->stream();
    }
}
