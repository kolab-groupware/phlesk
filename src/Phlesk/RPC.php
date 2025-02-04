<?php

/**
    RPC Helper class

    PHP Version 5

    @category  PHP
    @package   Phlesk
    @author    Jeroen van Meeuwen (Kolab Systems) <vanmeeuwen@kolabsys.com>
    @author    Christian Mollekopf (Kolab Systems) <mollekopf@kolabsys.com>
    @copyright 2019 Kolab Systems AG <contact@kolabsystems.com>
    @license   GPLv3 (https://www.gnu.org/licenses/gpl.txt)
    @link      https://pxts.ch
 */
namespace Phlesk;

class RPC
{
    private $rpc;

    public function __construct()
    {
        $this->rpc = \pm_ApiRpc::getService();
    }

    public function requestMailServiceForDomain($domainID)
    {
        $prefs = $this->siteMailPrefs($domainID);

        return $prefs['mailservice'];
    }

    private function siteMailPrefs($domainID)
    {
        $request = "
            <mail>
                <get_prefs>
                   <filter>
                      <site-id>{$domainID}</site-id>
                    </filter>
                </get_prefs>
            </mail>
        ";

        $result = $this->rpc->call($request)->mail->get_prefs->result->prefs;

        return [
            'mailservice'         => $result->{'mailservice'} == "true",
            'nonexistent-user'    => $result->{'nonexistent-user'},
            'spam-protect-sign'   => $result->{'spam-protect-sign'} == "true",
            'webmail'             => $result->{'webmail'},
            'webmail-certificate' => $result->{'webmail-certificate'},
        ];
    }

    public function isPoweruserModeEnabled()
    {
        $request = "
            <server>
                <get>
                    <gen_info/>
                </get>
            </server>
        ";
        $result = $this->rpc->call($request, 'admin');

        foreach ($result->server->get->result->gen_info as $gen_info) {
            if ((string)$gen_info->mode == "poweruser") {
                return true;
            }
        }
        return false;
    }
}
