<?php

namespace Icinga\Module\Ddolab\Controllers;

use Icinga\Module\Businessprocess\Html\Element;
use Icinga\Module\Businessprocess\Html\HtmlTag;
use Icinga\Module\Businessprocess\Html\Link;
use Icinga\Module\Businessprocess\Web\Component\ActionBar;
use Icinga\Module\Ddolab\Web\Controller;
use Icinga\Web\Notification;

class ControlController extends Controller
{
    public function indexAction()
    {
        $title = $this->translate('DDO Control');
        $this->setTitle($title);
        $this->singleTab($title);

        $this->addTitle('Welcome');


        $actions = new ActionBar();
        $this->content()->add($actions);
        $actions->add(
            Link::create(
                $this->translate('Show Hosts'),
                'ddolab/hosts',
                null,
                array(
                    'data-base-target' => '_next',
                    'class' => 'icon-host',
                )
            )
        )->add(
            Link::create(
                $this->translate('Restart'),
                'ddolab/control',
                array('action' => 'reload'),
                array(
                    'class' => 'icon-reschedule',
                )
            )
        )->add(
            Link::create(
                $this->translate('Wipe'),
                'ddolab/control',
                array('action' => 'wipe'),
                array(
                    'class' => 'icon-remove',
                )
            )
        );

        foreach (array(10, 100, 1000, 10000) as $cnt) {
            $actions->add(
                Link::create(
                    $cnt,
                    'ddolab/control',
                    array('add' => $cnt),
                    array('class' => 'icon-plus')
                )
            );
        }

        $actions->add(
            Link::create(
                '10 (fast)',
                'ddolab/control',
                array(
                    'add'      => 10,
                    'template' => 'fast-dummy-host',
                ),
                array('class' => 'icon-plus')
            )
        );

        if ($add = (int) $this->params->get('add')) {
            $template = $this->params->get('template', 'Random Fortune');
            $this->api()->runConsoleCommand(
                sprintf('addRandomHosts(%d, "%s")', $add, $template)
            );

            $this->redis()->lpush('icinga::trigger::configchange', 'add' + $add);
            Notification::success(sprintf('%d hosts created', $add));
            $this->redirectNow('ddolab/control');
        }

        if ($this->params->get('action') === 'reload') {
            $result = $this->api()->reloadNow();
            if ($result === true) {
                Notification::success('Reload has been triggered');
            } else {
                Notification::warning(sprintf('Tried to reload, got "%s"', $result));
            }
            $this->redis()->lpush('icinga::trigger::configchange', 'reload-from-web');
            $this->redirectNow('ddolab/control');
        } elseif ($this->params->get('action') === 'wipe') {
            $this->api()->deleteModule('director');
            $this->redis()->lpush('icinga::trigger::configchange', 'reload-from-web');
            $this->redirectNow('ddolab/control');
        }

        $this->content()->add(
            Element::create('span', array(
                'style' => 'float: right; font-size: 12em; margin: 0; padding: 0; margin-top: -0.4em'
            ))->add(Element::create('i', array('class' => 'icon-gauge')))
        )->add(
            HtmlTag::p(
                'This DDO Control Center allows you to play around with the'
                . ' experimental DDO data backend. Please use above links to'
                . ' add random host objects or to restart the Icinga daemon.'
            )->addAttributes(array('style' => 'margin-top: 2em'))
        );
    }
}
