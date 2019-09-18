<?php

namespace Phlesk;

class Installer
{
    /**
        Explicitly invoked if not already installed

        @return void
     */
    public function install()
    {
    }

    public function isInstalled()
    {
        return true;
    }

    /**
        Automatically invoked on before extension installation

        @return void
     */
    public function preInstall()
    {
    }

    public function postInstall()
    {
    }

    public function preUninstall()
    {
    }
}
