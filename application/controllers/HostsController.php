<?php

namespace Icinga\Module\Ddolab\Controllers;

use Icinga\Module\Ddolab\HostStateVolatile;
use Icinga\Module\Ddolab\View\HostsView;
use Icinga\Module\Ddolab\Web\Component\HostStateSummary;
use Icinga\Module\Ddolab\Web\Controller;
use Icinga\Module\Ddolab\Web\HostsTable;
use Icinga\Web\Notification;

class HostsController extends Controller
{
    public function indexAction()
    {
        $action = $this->params->shift('action');
        $hostname = $this->params->get('host');
        if ($action === 'checkNow') {
            $res = $this->api()->checkHostAndWaitForResult($hostname, 2);
            if ($res === false) {
                Notification::warning('Scheduled a new check, got no new result yet');
            }

            $this->redirectNow('ddolab/hosts');
        } elseif ($action === 'ack') {
            $this->api()->acknowledgeHostProblem(
                $hostname,
                $this->Auth()->getUser()->getUsername(),
                "I'm working on this"
            );
        } elseif ($action === 'removeAck') {
            $this->api()->removeHostAcknowledgement($hostname);
        }

        if ($action) {
            usleep(100000);
            $this->redirectNow('ddolab/hosts');
        }

        $this->setAutorefreshInterval(1);
        $title = $this->translate('Hosts');
        $this->singleTab($title);
        $this->addTitle($title);
        $this->content()->add($this->getHostsTable());
    }

    protected function getHostsTable()
    {
        $table = new HostsTable();
        $view = new HostsView($this->ddo());
        $view->addRowObserver('Icinga\\Module\\Ddolab\\HostStateVolatile::enrichRow');
        $view->addRowObserver(array($table, 'addHostLink'));
        $view->addRowObserver(array($table, 'renderStateColumn'));
        HostStateVolatile::setRedis($this->redis());
        // $table->setCaption('Just a bunch of hosts');
        // $table->header();
        // $table->footer();
        $table->renderRows($view->fetchRows());
        return $table;
    }
}
