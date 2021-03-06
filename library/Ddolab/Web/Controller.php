<?php

namespace Icinga\Module\Ddolab\Web;

use Icinga\Application\Config;
use Icinga\Exception\ConfigurationError;
use Icinga\Module\Businessprocess\Html\HtmlTag;
use Icinga\Module\Businessprocess\Web\Component\Content;
use Icinga\Module\Businessprocess\Web\Component\Controls;
use Icinga\Module\Businessprocess\Web\Component\Tabs;
use Icinga\Module\Ddolab\DdoDb;
use Icinga\Module\Ddolab\Redis;
use Icinga\Module\Director\Db;
use Icinga\Module\Director\Objects\IcingaEndpoint;
use Icinga\Web\Controller as WebController;

class Controller extends WebController
{
    /** @var \Zend_Db_Adapter_Abstract */
    private $db;

    /** @var DdoDb */
    private $ddo;

    /** @var Redis */
    private $redis;

    private $mytabs;

    private $directorDb;

    private $api;

    public function init()
    {
        $this->controls();
        $this->content();
        $this->setViewScript('default');
    }

    /**
     * @return Controls
     */
    protected function controls()
    {
        if ($this->view->controls === null) {
            $this->view->controls = Controls::create();
        }

        return $this->view->controls;
    }

    protected function setViewScript($name)
    {
        $this->_helper->viewRenderer->setNoController(true);
        $this->_helper->viewRenderer->setScriptAction($name);
        return $this;
    }

    protected function setTitle($title)
    {
        $args = func_get_args();
        array_shift($args);
        $this->view->title = vsprintf($title, $args);
        return $this;
    }

    protected function addTitle($title)
    {
        $args = func_get_args();
        array_shift($args);
        $this->view->title = vsprintf($title, $args);
        $this->controls()->add(HtmlTag::h1($this->view->title));
        return $this;
    }

    /**
     * @param $label
     * @return Tabs
     */
    protected function singleTab($label)
    {
        return $this->tabs()->add(
            'tab',
            array(
                'label' => $label,
                'url'   => $this->getRequest()->getUrl()
            )
        )->activate('tab');
    }

    /**
     * @return Tabs
     */
    protected function tabs()
    {
        // Todo: do not add to view once all of them render controls()
        if ($this->mytabs === null) {
            $tabs = new Tabs();
            $this->controls()->prepend($tabs);
            $this->mytabs = $tabs;
        }

        return $this->mytabs;
    }

    /**
     * @return Content
     */
    protected function content()
    {
        if ($this->view->content === null) {
            $this->view->content = Content::create();
        }

        return $this->view->content;
    }

    /**
     * @return \Zend_Db_Adapter_Abstract
     */
    protected function db()
    {
        if ($this->db === null) {
            $this->db = $this->ddo()->getDbAdapter();
        }

        return $this->db;
    }

    /**
     * @return \Predis\Client;
     */
    protected function redis()
    {
        if ($this->redis === null) {
            $this->redis = Redis::instance(true);
        }

        return $this->redis;
    }

    /**
     * @param null $endpointName
     * @return \Icinga\Module\Director\Core\CoreApi
     */
    protected function api($endpointName = null)
    {
        if ($this->api === null) {
            if ($endpointName === null) {
                $endpoint = $this->directorDb()->getDeploymentEndpoint();
            } else {
                $endpoint = IcingaEndpoint::load($endpointName, $this->db());
            }

            $this->api = $endpoint->api();
        }

        return $this->api;
    }

    /**
     * @return Db
     */
    protected function directorDb()
    {
        if ($this->directorDb === null) {
            // Hint: not using $this->Config() intentionally. This allows
            // CLI commands in other modules to use this as a base class.
            $resourceName = Config::module('director')->get('db', 'resource');
            if ($resourceName) {
                $this->directorDb = Db::fromResourceName($resourceName);
            }
        }

        return $this->directorDb;
    }

    /**
     * @return DdoDb
     * @throws ConfigurationError
     */
    protected function ddo()
    {
        if ($this->ddo === null) {
            $resourceName = $this->Config()->get('db', 'resource');
            if ($resourceName) {
                $this->ddo = DdoDb::fromResourceName($resourceName);
            } else {
                throw new ConfigurationError('(ddolab) DDO is not configured correctly');
            }
        }

        return $this->ddo;
    }
}
