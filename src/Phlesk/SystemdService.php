<?php

namespace Phlesk;

class SystemdService extends \pm_SystemService_Service
{
    private $_name;
    private $_service_name;
    private $_id;

    public function __construct($_name, $_service_name, $_id)
    {
        $this->_name = $_name;
        $this->_service_name = $_service_name;
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
        return $this->systemctl("is-enabled") == 0;
    }

    public function isInstalled()
    {
        return $this->systemctl("status") != 4;
    }

    public function isRunning()
    {
        return $this->systemctl("status") == 0;
    }

    public function onRestart()
    {
        $this->systemctl("restart");
    }

    public function onStart()
    {
        $this->systemctl("start");
    }

    public function onStop()
    {
        $this->systemctl("stop");
    }

    private function systemctl($action)
    {
        $result = \Phlesk::exec(
            ["systemctl", "{$action}", "{$this->_service_name}"],
            true
        );

        return $result['code'];
    }
}
