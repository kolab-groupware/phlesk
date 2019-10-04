<?php

namespace Phlesk;

class SystemdService extends \pm_SystemService_Service
{
    private $name;
    private $serviceName;
    private $id;

    public function __construct($_name, $_serviceName, $_id)
    {
        $this->name = $_name;
        $this->serviceName = $_serviceName;
        $this->id = $_id;
    }

    public function getId()
    {
        return $this->id;
    }

    public function getName()
    {
        return $this->name;
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
            ["systemctl", "{$action}", "{$this->serviceName}"],
            true
        );

        return $result['code'];
    }
}
