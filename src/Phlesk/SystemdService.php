<?php

namespace Phlesk;

class SystemdService extends \pm_SystemService_Service
{
    private $_name;
    private $_serviceName;
    private $_id;

    public function __construct($_name, $_serviceName, $_id)
    {
        $this->_name = $_name;
        $this->_serviceName = $_serviceName;
        $this->_id = $_id;
    }

    public function getId()
    {
        return $this->_id;
    }

    public function getName()
    {
        return $this->_name;
    }

    public function isConfigured()
    {
        return $this->_systemctl("is-enabled") == 0;
    }

    public function isInstalled()
    {
        return $this->_systemctl("status") != 4;
    }

    public function isRunning()
    {
        return $this->_systemctl("status") == 0;
    }

    public function onRestart()
    {
        $this->_systemctl("restart");
    }

    public function onStart()
    {
        $this->_systemctl("start");
    }

    public function onStop()
    {
        $this->_systemctl("stop");
    }

    private function _systemctl($action)
    {
        $result = \Phlesk::exec(
            ["systemctl", "{$action}", "{$this->_serviceName}"],
            true
        );

        return $result['code'];
    }
}
