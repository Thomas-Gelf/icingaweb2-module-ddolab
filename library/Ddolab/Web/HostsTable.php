<?php

namespace Icinga\Module\Ddolab\Web;

use Icinga\Date\DateFormatter;
use Icinga\Module\Businessprocess\Html\Element;
use Icinga\Module\Businessprocess\Html\Link;
use Icinga\Module\Ddolab\Web\Component\Table;

class HostsTable extends Table
{
    protected $stateClasses = array(
        0 => 'up',
        1 => 'up', // TODO: get rid of this
        2 => 'down',
        3 => 'unreachable', // nay
        // 'unknown',
        99 => 'pending',
    );

    protected $stateNames = array(
        0 => 'UP',
        1 => 'UP', // TODO: get rid of this
        2 => 'DOWN',
        'UNREACHABLE',
        'UNKNOWN',
        99 => 'PENDING',
    );

    /** @inheritdoc */
    protected $defaultAttributes = array(
        'class' => array(
            'simple',
            'common-table',
            'table-row-selectable',
            'state-table',
        ),
        'data-base-target' => '_next',
    );

    public function getColumnsToBeRendered()
    {
        return array('renderedState', 'host');
    }

    public function renderStateColumn($row)
    {
        $row->renderedState = Element::create(
            'span',
            array('class' => 'state')
        )->addContent(
            $this->stateNames[$row->state]
        )->add(
            Element::create(
                'span',
                array('class' => array('relative-time', 'time-since'))
            )->setContent(
                DateFormatter::timeSince(
                    $row->last_state_change / 1000000
                )
            )
        );
    }

    public function addHostLink($row)
    {
        $hostname = $row->host;
        $row->host = array();
        if (strpos($hostname, 'random') === false) {
            $row->host[] = Link::create(
                $hostname,
                'monitoring/host/show',
                array('host' => $hostname)
            );
        } else {
            $row->host[] = Link::create(
                $hostname,
                'director/inspect/object',
                array(
                    'type'   => 'host',
                    'plural' => 'hosts',
                    'name'   => $hostname
                )
            );
        }
        $this->addRescheduleLink($row, $hostname);
        $this->addAckLink($row, $hostname);

        if (property_exists($row, 'output') && !empty($row->output)) {
            $this->addHostOutput($row, $hostname);
        }
    }

    protected function addRescheduleLink($row, $hostname)
    {
        $row->host[] = ' ';
        $row->host[] = Link::create(
            'Check now',
            'ddolab/hosts',
            array(
                'action' => 'checkNow',
                'host'   => $hostname,
            ),
            array(
                'class' => 'icon-reschedule',
                'data-base-target' => '_self',
            )
        );
    }

    protected function addAckLink($row, $hostname)
    {
        if ($row->problem === 'n' || (int) $row->state === 99) {
            return;
        }
        if ($row->acknowledged === 'y') {
            $action = 'removeAck';
            $title = 'Remove Ack';
        } else {
            $action = 'ack';
            $title = 'Ack';
        }
        $row->host[] = ' ';
        $row->host[] = Link::create(
            $title,
            'ddolab/hosts',
            array(
                'action' => $action,
                'host'   => $hostname,
            ),
            array(
                'class' => 'icon-reschedule',
                'data-base-target' => '_self',
            )
        );
    }

    protected function addHostOutput($row, $hostname)
    {
        $row->host[] = Element::create(
            'p',
            array('class' => 'overview-plugin-output')
        )->setContent($row->output);
    }

    public function getRowClasses($row)
    {
/*
        'checksum'              => null,
        'host'                  => null,
        'state'                 => null,
        'hard_state'            => null,
        'state_type'            => null,
        'attempt'               => null,
        'problem'               => null,
        'reachable'             => null,
        'last_state_change'     => null,
        'last_comment_checksum' => null,
        'check_source_checksum' => null,
 */

        $classes = array($this->stateClasses[$row->state]);

        if ($row->problem === 'y') {
            $classes[] = 'problem';
        }

        if ($row->acknowledged === 'y') {
            $classes[] = 'handled';
            $classes[] = 'acknowledged';
        }

        if ($row->in_downtime === 'y') {
            $classes[] = 'handled';
            $classes[] = 'in_downtime';
        }

        return $classes;
    }
}
