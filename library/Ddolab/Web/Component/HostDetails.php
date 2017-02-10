<?php

namespace Icinga\Module\Ddolab\Web\Component;

use Icinga\Module\Businessprocess\Html\BaseElement;
use Icinga\Module\Businessprocess\Html\Element;
use Icinga\Module\Businessprocess\Html\Link;
use Icinga\Module\Ddolab\HostObject;
use Icinga\Module\Ddolab\HostState;
use Icinga\Module\Ddolab\HostStateVolatile;

class HostDetails extends BaseElement
{
    protected $contentSeparator = "\n";

    /** @var string */
    protected $tag = 'div';

    public function __construct(HostObject $host, HostState $state, HostStateVolatile $volatile)
    {
        $this->add(
            Element::create('h2')->setContent('Plugin Output')
        )->add(
            Element::create('pre')->setContent($volatile->output)
        )->add(
            Element::create('h2')->setContent('Problem handling')
        )->add(
            Element::create('h2')->setContent('Notifications')
        )->add(
            Element::create('h2')->setContent('Check execution')
        )->addContent(
            Link::create(
                $host->check_command,
                'director/command',
                array('name' => $host->check_command),
                array('data-base-target' => '_next')
            )
        )->add(
            Element::create('h2')->setContent('Feature Commands')
        );
    }
}
