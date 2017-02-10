<?php

namespace Icinga\Module\Ddolab\Controllers;

use Icinga\Module\Ddolab\HostObject;
use Icinga\Module\Ddolab\HostState;
use Icinga\Module\Ddolab\HostStateVolatile;
use Icinga\Module\Ddolab\Web\Component\HostActionBar;
use Icinga\Module\Ddolab\Web\Component\HostDetails;
use Icinga\Module\Ddolab\Web\Component\HostHeader;
use Icinga\Module\Ddolab\Web\Controller;

class HostController extends Controller
{
    public function showAction()
    {
        $name = $this->params->get('name');
        $checksum = sha1($name, true);

        $host = HostObject::load($checksum, $this->ddo());
        $state = HostState::load($checksum, $this->ddo());
        $volatile = HostStateVolatile::fromRedis($this->redis(), $checksum);

        $this->setAutorefreshInterval(10);
        $this->controls()->attributes()->add('class', 'controls-separated');
        $this->singleTab($this->translate('Host'));
        $this->setTitle(sprintf($this->translate('Host: %s'), $name));

        $this->controls()->add(
            new HostHeader($host, $state)
        )->add(
            new HostActionBar($host, $state)
        );

        $this->content()->add(
            new HostDetails($host, $state, $volatile)
        );
    }
}
