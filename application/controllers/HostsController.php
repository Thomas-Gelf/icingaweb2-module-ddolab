<?php

namespace Icinga\Module\Ddolab\Controllers;

use Exception;
use Icinga\Module\Ddolab\HostStateVolatile;
use Icinga\Module\Ddolab\StateObject;
use Icinga\Module\Ddolab\View\HostsView;
use Icinga\Module\Ddolab\Web\Component\HostStateSummaryBadges;
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
            try {
                $res = $this->api()->checkHostAndWaitForResult($hostname, 2);
                if ($res === false) {
                    Notification::warning('Scheduled a new check, got no new result yet');
                }
            } catch (Exception $e) {
                Notification::error(sprintf(
                    'Rescheduling the check failed: %s',
                    $e->getMessage()
                ));
            }

            $this->redirectNow('ddolab/hosts');
        } elseif ($action === 'ack') {
            try {
                $this->api()->acknowledgeHostProblem(
                    $hostname,
                    $this->Auth()->getUser()->getUsername(),
                    "I'm working on this"
                );
            } catch (Exception $e) {
                Notification::error(sprintf(
                    'Acknowledging the problem failed: %s',
                    $e->getMessage()
                ));
            }
        } elseif ($action === 'removeAck') {
            try {
                $this->api()->removeHostAcknowledgement($hostname);
            } catch (Exception $e) {
                Notification::error(sprintf(
                    'Failed to remove this acknowledgement: %s',
                    $e->getMessage()
                ));
            }
        }

        if ($action) {
            usleep(100000);
            $this->redirectNow('ddolab/hosts');
        }

        $this->setAutorefreshInterval(10);
        $title = $this->translate('Hosts');
        $this->singleTab($title);
        $this->controls()->add($this->createSummary());
        $this->addTitle($title);
        $this->content()->add($this->getHostsTable());
    }

    protected function createSummary()
    {
        $db = $this->ddo();
        $summary = $db->fetchPairs(
            $db->select()->from(
                array('hs' => 'host_state'),
                array(
                    'severity' => 'hs.severity',
                    'cnt' => 'COUNT(*)',
                )
            )->group('hs.severity')
        );

        return new HostStateSummaryBadges($summary);
    }

    protected function getHostsTable()
    {
        $table = new HostsTable();
        $view = new HostsView($this->ddo());

        if ($state = $this->params->get('state')) {
            $view->baseQuery()->where(
                'state = ?',
                StateObject::getStateForName($state)
            );
        }

        if ($handled = $this->params->get('handled')) {
            if ($handled === 'y') {
                $view->baseQuery()
                    ->where('acknowledged = ?', $handled)
                    ->orWhere('in_downtime = ?', $handled);
            } else {
                $view->baseQuery()
                    ->where('acknowledged = ?', $handled)
                    ->where('in_downtime = ?', $handled);
            }
        }

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
