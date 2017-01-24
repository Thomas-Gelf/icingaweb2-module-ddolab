<?php

namespace Icinga\Module\Ddolab\Web;

use Icinga\Exception\ConfigurationError;
use Icinga\Module\Businessprocess\Web\Component\Content;
use Icinga\Module\Businessprocess\Web\Component\Controls;
use Icinga\Module\Ddolab\DdoDb;
use Icinga\Web\Controller as WebController;

class Controller extends WebController
{
    /** @var \Zend_Db_Adapter_Abstract */
    private $db;

    /** @var DdoDb */
    private $ddo;

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
